@extends('errors.layout')
@section('code', '404')
@section('title', 'Página no encontrada')
@section('message', $exception?->getMessage() && ! in_array($exception->getMessage(), ['', 'This action is unauthorized.', 'Not Found'], true) && 404 !== 500 ? $exception->getMessage() : 'El recurso que busca no existe o fue eliminado.')
