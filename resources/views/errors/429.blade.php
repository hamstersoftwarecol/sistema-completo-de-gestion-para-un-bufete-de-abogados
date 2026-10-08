@extends('errors.layout')
@section('code', '429')
@section('title', 'Demasiadas solicitudes')
@section('message', $exception?->getMessage() && ! in_array($exception->getMessage(), ['', 'This action is unauthorized.', 'Not Found'], true) && 429 !== 500 ? $exception->getMessage() : 'Espere un momento antes de intentarlo de nuevo.')
