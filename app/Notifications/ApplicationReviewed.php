<?php

namespace App\Notifications;

use App\Http\Resources\NotificationResource;
use App\Models\Application;
use App\Support\MailText;
use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public $application;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    // Only the email waits in the queue; the in-app notice shows right away.
    public function viaConnections(): array
    {
        return ['database' => 'sync', 'broadcast' => 'sync'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $application = $this->application;
        $company = $application->company->display_name;
        $role = $application->opportunity->title;

        $with = ['company' => $company, 'role' => $role];
        [$subject, $headline, $body, $tip] = match ($application->status) {
            'interview' => [__(':company would like to interview you', $with), __('You have an interview request'), __(':company wants to talk with you about :role.', $with), __('Watch your inbox and your :app messages for the next steps.', ['app' => config('app.name')])],
            'offer' => [__('Good news from :company', $with), __('You received an offer'), __(':company made you an offer for :role. Congratulations!', $with), __('Reply to the employer soon so they know your answer.')],
            'closed' => [__('Update on your application to :company', $with), __('Your application was closed'), __(':company decided not to move forward with your application for :role.', $with), __('It happens to everyone. New roles come in every day, so keep going.')],
            'review' => [__(':company is reviewing your application', $with), __('Your application is under review'), __(':company is now reviewing your application for :role.', $with), null],
            default => [__(':company saw your application', $with), __('Your application was seen'), __(':company opened your application for :role.', $with), null],
        };

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.application-updated', [
                'candidate' => MailText::plain(MailText::firstName($notifiable->name), 80),
                'headline' => MailText::plain($headline, 200),
                'body' => MailText::plain($body, 400),
                'tip' => $tip,
                'role' => MailText::plain($role, 160),
                'company' => MailText::plain($company, 120),
                'statusLabel' => __(ucfirst($application->status)),
                'url' => route('applications.show', $application),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $companyName = e($this->application->company->display_name);
        $opportunityTitle = e($this->application->opportunity->title);

        return [
            'title' => __(":company's Reply", ['company' => $this->application->company->display_name]),
            'body' => __('<span>:company</span> has reviewed your application for <span>:role</span>.', ['company' => $companyName, 'role' => $opportunityTitle]),
            'action' => route('applications.show', $this->application->id, false),
        ];
    }

	public function toBroadcast($notifiable)
	{
		return new BroadcastMessage([
			'toast' => [
				'message' => __(':company has reviewed your application.', ['company' => $this->application->company->display_name]),
				'type' => 'notification',
			],
			'data' => new NotificationResource(NotificationModel::find($this->id))
		]);
	}
}
