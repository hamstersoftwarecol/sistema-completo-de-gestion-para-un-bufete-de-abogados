@extends('errors.layout')
@section('code', '503')
@section('title', 'En mantenimiento')
@section('message', $exception?->getMessage() && ! in_array($exception->getMessage(), ['', 'This action is unauthorized.', 'Not Found'], true) && 503 !== 500 ? $exception->getMessage() : 'El sistema está en mantenimiento. Vuelva en unos minutos.')
