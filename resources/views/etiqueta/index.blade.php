<x-layout title="Etiqueta">
    <ul>
        @foreach ($etiquetas as $etiqueta)
        <li>{{ $etiqueta }}</li>
        @endforeach
    </ul>
</x-layout>
