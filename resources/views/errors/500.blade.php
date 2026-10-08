@extends('errors.layout')
@section('code', '500')
@section('title', 'Error del servidor')
@section('message', $exception?->getMessage() && ! in_array($exception->getMessage(), ['', 'This action is unauthorized.', 'Not Found'], true) && 500 !== 500 ? $exception->getMessage() : 'Ocurrió un error inesperado. Revise storage/logs/laravel.log.')
