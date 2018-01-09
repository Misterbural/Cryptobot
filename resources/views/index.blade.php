@extends('template')

@section('title')
    Index - Cryptobot
@endsection

@section('content')
    <h1>Accès rapides aux pages :</h1>
    <ul>
        <li>
            Test peaks detection
            <ul>
                @foreach ($currencies as $currencie)
                    <li><a href="{{ route('supportandresistance.peaks', ['market' => 'BTC-' . $currencie]) }}">BTC-{{$currencie}}</a></li>
                @endforeach
            </ul>
        </li>
        <li></li>
    </ul>
@endsection
