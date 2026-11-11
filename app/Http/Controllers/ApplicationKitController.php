<?php

namespace App\Http\Controllers;

use App\Ai\ApplicationKits;
use App\Billing\Credits;
use App\Jobs\GenerateApplicationKit;
use App\Models\Application;
use App\Models\ApplicationKit;
use App\Models\Opportunity;
use App\Support\Cv;
use App\Support\CvPdf;
use App\Support\CvTemplates;
use App\Support\LanguageGuess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

// Tailored CVs and cover letters: one kit per candidate, role and language.
class ApplicationKitController extends Controller
{
    public function index(Request $request)
    {
        $candidate = $request->user()->ensureCandidateProfile() ?? abort(403);
        $kits = ApplicationKit::where('candidate_id', $candidate->id)->with('opportunity.company.user', 'opportunity.translations')->latest('updated_at')->get();

        return Inertia::render('Kits/Index', ['kits' => $kits->map(fn ($kit) => $this->summary($kit))]);
    }

    public function store(Request $request, Opportunity $opportunity, ApplicationKits $writer, Credits $credits)
    {
        $user = $request->user();
        $candidate = $user->ensureCandidateProfile() ?? abort(403);
        abort_unless($opportunity->publication_status === 'published' && $opportunity->isOpen(), 404);
        $data = $request->validate([
            'locale' => ['required', Rule::in(ApplicationKits::locales($opportunity))],
            'part' => ['nullable', Rule::in(ApplicationKit::PARTS)],
        ]);
        $part = $data['part'] ?? 'both';
        abort_unless($writer->enabled(), 503, 'Tailored CVs and letters are not available right now.');

        $kit = ApplicationKit::firstOrCreate(
            ['candidate_id' => $candidate->id, 'opportunity_id' => $opportunity->id, 'locale' => $data['locale']],
            ['style' => CvTemplates::style($candidate->mainResume?->style ?? ApplicationKit::where('candidate_id', $candidate->id)->whereNotNull('style')->latest('updated_at')->value('style'))],
        );
        // Asking again for a part that is already written just opens the kit.
        $missing = ['cv' => ! $kit->cv, 'letter' => blank($kit->cover_letter), 'both' => ! $kit->cv && blank($kit->cover_letter)][$part];
        if ($kit->wasRecentlyCreated || ($kit->status !== 'queued' && ($kit->status === 'failed' || $missing))) {
            if ($response = $this->start($user, $kit, $credits, $part)) { return $response; }
        }

        return response()->json(['kit' => $this->summary($kit->fresh('opportunity')), 'url' => route('kits.show', $kit)]);
    }

    public function show(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        $kit->load('opportunity.company.user', 'opportunity.translations');
        if ($request->wantsJson()) {
            return response()->json(['kit' => $this->detail($kit)]);
        }

        return Inertia::render('Kits/Show', ['kit' => $this->detail($kit), ...ResumeController::editorProps()]);
    }

    public function update(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        $writing = $kit->status === 'queued' ? ($kit->writing ?? 'both') : null;
        abort_if($writing && ($request->has('cv') && $writing !== 'letter' || $request->has('cover_letter') && $writing !== 'cv'), 409, 'This is still being written.');
        $data = $request->validate([
            'cover_letter' => 'nullable|string|max:20000',
            'cv' => 'sometimes|array',
            'cv.name' => 'nullable|string|max:150',
            'cv.contact' => 'nullable|array|max:8',
            'cv.contact.*' => 'nullable|string|max:255',
            ...CvTemplates::rules(),
        ]);
        // The listing page saves the letter alone, without the CV.
        $kit->update([
            ...($request->has('cover_letter') ? ['cover_letter' => trim((string) ($data['cover_letter'] ?? ''))] : []),
            ...(isset($data['cv']) ? ['cv' => $this->keptCv($kit, Cv::clean($request->input('cv', [])))] : []),
            ...(isset($data['style']) ? ['style' => CvTemplates::style([...($kit->style ?? []), ...$data['style']])] : []),
        ]);
        if ($request->wantsJson()) {
            return response()->json(['saved' => true]);
        }

        return back()->with('toast', ['type' => 'update', 'message' => 'Your CV and letter are saved.']);
    }

    public function regenerate(Request $request, ApplicationKit $kit, ApplicationKits $writer, Credits $credits)
    {
        $this->authorizeKit($request, $kit);
        abort_unless($kit->opportunity?->publication_status === 'published' && $kit->opportunity->isOpen(), 409, 'This listing is closed.');
        abort_unless($writer->enabled(), 503, 'Tailored CVs and letters are not available right now.');
        $part = $request->validate(['part' => ['nullable', Rule::in(ApplicationKit::PARTS)]])['part'] ?? 'both';
        if ($kit->status !== 'queued' && ($response = $this->start($request->user(), $kit, $credits, $part))) {
            return $response;
        }

        return response()->json(['kit' => $this->summary($kit->fresh('opportunity')), 'url' => route('kits.show', $kit)]);
    }

    public function destroy(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        $kit->delete();

        return redirect()->route('kits.index')->with('toast', ['type' => 'destroy', 'message' => 'The CV and letter were deleted.']);
    }

    public function cv(Request $request, ApplicationKit $kit, CvPdf $pdf)
    {
        $this->authorizeKit($request, $kit);
        abort_unless($kit->cv, 404);

        return $this->download($pdf->pdf($kit->cv, $kit->style, $kit->locale), $kit->fileName('cv'));
    }

    public function letter(Request $request, ApplicationKit $kit, CvPdf $pdf)
    {
        $this->authorizeKit($request, $kit);
        abort_unless($kit->cover_letter, 404);

        return $this->download($pdf->letterPdf($kit->cv ?: $this->sender($kit), $kit->cover_letter, $kit->style, $kit->locale), $kit->fileName('letter'));
    }

    // Takes a credit when needed and queues the draft of the CV, the letter or both. Returns an error response when the user has no credit.
    private function start($user, ApplicationKit $kit, Credits $credits, string $part = 'both')
    {
        if (! $credits->chargeFor($user, $kit)) {
            return response()->json(['message' => 'You have no credits left. Get more to tailor more CVs and letters.', 'billing_url' => route('billing.index')], 402);
        }
        $kit->update(['status' => 'queued', 'writing' => $part, 'error' => null, 'generations' => $kit->generations + 1]);
        GenerateApplicationKit::dispatch($kit->id);

        return null;
    }

    // A kit with only a letter keeps no CV until something besides the name and contact is filled in.
    private function keptCv(ApplicationKit $kit, array $cv): ?array
    {
        return $kit->cv || collect($cv)->except(['name', 'contact'])->filter()->isNotEmpty() ? $cv : null;
    }

    // The name and contact lines at the top of the letter, from the CV or else from the profile.
    private function sender(ApplicationKit $kit): array
    {
        if ($kit->cv) {
            return ['name' => $kit->cv['name'] ?? '', 'contact' => $kit->cv['contact'] ?? []];
        }
        $candidate = $kit->candidate()->with('user')->first();

        return ['name' => (string) $candidate?->user?->name, 'contact' => $candidate ? Cv::contact($candidate) : []];
    }

    private function authorizeKit(Request $request, ApplicationKit $kit): void
    {
        abort_unless($request->user()->isCandidate() && $kit->candidate_id === $request->user()->userable_id, 404);
    }

    private function download(string $pdf, string $name)
    {
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'"']);
    }

    private function summary(ApplicationKit $kit): array
    {
        $role = $kit->opportunity;
        return [
            'id' => $kit->id,
            'locale' => $kit->locale,
            'language' => LanguageGuess::name($kit->locale) ?? strtoupper($kit->locale),
            'status' => $kit->status,
            'writing' => $kit->status === 'queued' ? ($kit->writing ?? 'both') : null,
            'error' => $kit->error,
            'updated_at' => $kit->updated_at?->toIso8601String(),
            'updated_label' => $kit->updated_at?->diffForHumans(),
            'role' => $role ? [
                'id' => $role->id,
                'title' => $role->titleFor($kit->locale),
                'company' => $role->company?->display_name,
                'url' => $role->publicUrl($kit->locale),
                'is_open' => $role->isOpen(),
                'application_mode' => $role->application_mode,
                'application_url' => $role->application_url,
            ] : null,
        ];
    }

    private function detail(ApplicationKit $kit): array
    {
        $credits = app(Credits::class);
        return [
            ...$this->summary($kit),
            'cv' => $kit->cv ? Cv::clean($kit->cv) : null,
            'cover_letter' => $kit->cover_letter,
            'sender' => $this->sender($kit),
            'style' => CvTemplates::style($kit->style),
            'labels' => CvTemplates::labels($kit->locale),
            'application_id' => Application::where('candidate_id', $kit->candidate_id)->where('opportunity_id', $kit->opportunity_id)->value('id'),
            'rewrites_left' => $credits->enabled() ? max(0, $kit->credits_used * $credits->draftsPerCredit() - $kit->generations) : null,
        ];
    }
}
