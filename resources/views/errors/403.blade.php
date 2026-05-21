@extends('layouts.app')

@section('content')
  <h1>Forbidden (403)</h1>
  <p>
    {{ $exception->getMessage() ?: "You're not allowed to access this page." }}
  </p>
@endsection
