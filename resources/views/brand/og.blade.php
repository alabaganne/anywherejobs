<!doctype html>
<html>
<head>
<meta charset="utf-8">
{{-- The share image, rebuilt by `php artisan brand:images`. --}}
<style>
@font-face { font-family: 'Display'; font-weight: 600; src: url('{{ $display }}') format('woff2'); }
@font-face { font-family: 'Body'; font-weight: 400; src: url('{{ $body }}') format('woff2'); }
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { width: 1200px; height: 630px; overflow: hidden; background: #001f6d; }
.page { position: relative; width: 1200px; height: 630px; padding: 64px 72px; font-family: 'Body', sans-serif; color: #fff; }
.brand { display: flex; align-items: center; gap: 16px; font-family: 'Display'; font-weight: 600; font-size: 32px; letter-spacing: -0.01em; }
.brand img { width: 56px; height: 56px; }
h1 { margin-top: 58px; width: 640px; font-family: 'Display'; font-weight: 600; font-size: 60px; line-height: 1.08; letter-spacing: -0.02em; }
h1 span { color: #96b3ff; }
p { margin-top: 26px; font-size: 24px; color: #d6def5; }
.chips { position: absolute; left: 72px; bottom: 58px; display: flex; gap: 12px; }
.chips span { padding: 10px 18px; border: 1px solid rgba(214, 222, 245, .35); border-radius: 999px; font-size: 19px; font-weight: 500; color: #e8edf6; }
.art { position: absolute; right: 70px; top: 170px; width: 360px; height: 300px; }
.art .blob { position: absolute; inset: 40px 0 30px 0; border-radius: 50%; background: #f2f6ff; }
.art img { position: absolute; left: 10px; top: 0; width: 340px; height: 300px; object-fit: contain; }
.spark { position: absolute; color: #f78c78; font-size: 26px; }
</style>
</head>
<body>
<div class="page">
    <div class="brand"><img src="{{ $logo }}" alt="">{{ $name }}</div>
    <h1>IT internships and jobs <span>worldwide</span></h1>
    <p>Stages et emplois IT dans le monde entier</p>
    <div class="chips"><span>Software</span><span>Data and AI</span><span>DevOps and cloud</span><span>Security</span></div>
    <div class="art"><div class="blob"></div><img src="{{ $mascot }}" alt=""></div>
    <div class="spark" style="right: 420px; top: 200px">✦</div>
    <div class="spark" style="right: 96px; top: 150px; color: #96b3ff; font-size: 20px">✦</div>
</div>
</body>
</html>
