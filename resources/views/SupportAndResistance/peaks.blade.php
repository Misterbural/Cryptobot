@extends('template')

@section('title')
    Peaks {{ $market }} - Cryptobot
@endsection

@section('content')
    <h1>Peaks for market {{ $market }}</h1>
     <div id="chartdiv" style="width: 1000px; height: 600px;"></div>
    @foreach ($minimas as $minima)
        <div style="display: block;width: 500px;padding:20px;border: 1px solid black; border-radius:3px;margin:20px">
            <strong>Minimas N°{{ $loop->iteration }}</strong>
            <ul>
                <li>{{ $minima->open_time }} - {{ $minima->close_price }}</li>
            </ul>
        </div>
    @endforeach
@endsection

@section('scripts')
    <script>

    var chartData = JSON.parse('<?php echo $json_candles; ?>');
    
    var chart = AmCharts.makeChart( "chartdiv", {
      "type": "stock",
      "theme": "light",

      "categoryAxesSettings": {
        "minPeriod": "5mm",
        'groupToPeriods': ['5mm'],
      },

      "dataSets": [ {
        "fieldMappings": [ {
          "fromField": "open_price",
          "toField": "open"
        }, {
          "fromField": "close_price",
          "toField": "close"
        }, {
          "fromField": "max_price",
          "toField": "high"
        }, {
          "fromField": "min_price",
          "toField": "low"
        } 
        ],

        "color": "#7f8da9",
        "dataProvider": chartData,
        "title": "{{ $market }}",
        "categoryField": "open_time"
      }],


      "panels": [ {
          "title": "Value",
          "showCategoryAxis": false,
          "percentHeight": 70,
          "valueAxes": [ {
            "dashLength": 5
          } ],

          "categoryAxis": {
            "dashLength": 5,
          },

          "stockGraphs": [ {
            "type": "candlestick",
            "id": "g1",
            "openField": "open",
            "closeField": "close",
            "highField": "high",
            "lowField": "low",
            "valueField": "close",
            'bulletField': 'bullet',
            "lineColor": "#27ae60",
            "fillColors": "#27ae60",
            "negativeLineColor": "#c0392b",
            "negativeFillColors": "#c0392b",
            'bulletColor': '#3498db',
            "fillAlphas": 1,
            "useDataSetColors": false,
            "showBalloon": true,
            'balloonText': 'Date:<b>[[category]]</b><br/>Open:<b>[[open]]</b><br>Low:<b>[[low]]</b><br>High:<b>[[high]]</b><br>Close:<b>[[close]]</b><br>',
        } ],

        "stockLegend": {
            "valueTextRegular": undefined,
        }

      }],

      "chartScrollbarSettings": {
        "graph": "g1",
        "graphType": "line",
        "usePeriod": "5mm"
      },

    } ); 
    </script>
@endsection
