@extends('errors.layout')
@section('code', '419')
@section('title', 'Sesión expirada')
@section('message', $exception?->getMessage() && ! in_array($exception->getMessage(), ['', 'This action is unauthorized.', 'Not Found'], true) && 419 !== 500 ? $exception->getMessage() : 'Su sesión expiró por inactividad. Vuelva a intentarlo.')
