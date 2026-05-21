@extends('layouts.app')

@section('content')
  <h1>Page not found</h1>
  <p>
    {{ $exception->getMessage() ?: 'This is not the page you were looking for.' }}
  </p>
@endsection
