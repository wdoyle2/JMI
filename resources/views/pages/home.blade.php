@extends('layouts.app')

@section('content')
  <img src="{{ asset('images/wind4life_banner.png') }}"
       alt="Wind 4 life Banner"
       class="w-50" />
  <h1 class="mt-3">Wind4Life</h1>
  <p class="lead">
    Real-time wind data from anemometers around the world.
  </p>
  <p>
    Welcome to the <b>Wind For Life</b> project, a backend for a non-profit
    organization collecting wind speed readings from a fleet of anemometers.
  </p>
  <div class="alert alert-info" role="alert">
    <p class="mb-1"><strong>Admin account:</strong> username <code>admin</code>, password <code>admin</code>.</p>
    <p class="mb-1">API docs at <a href="/api/docs">/api/docs</a> (if Scribe is installed).</p>
    <p class="mb-0">Use <code>POST /api/auth-token</code> to obtain a Sanctum token.</p>
  </div>
  <p>
    Take a look at the <a href="/about">About</a> page for thoughts on the project.
  </p>
@endsection
