@extends('errors.layout')
@section('code', '403')
@section('title', 'Acceso denegado')
@section('message', $exception?->getMessage() && ! in_array($exception->getMessage(), ['', 'This action is unauthorized.', 'Not Found'], true) && 403 !== 500 ? $exception->getMessage() : 'No tiene permiso para ver este contenido. Cada abogado sólo puede acceder a sus propios asuntos.')
