<script>
    window.masterfile = {
        chargeOfMinutes: {{ $items['charge_of_minutes']}},
        minutesPerCharge: {{ $items['minutes_per_charge']}},
        durationPriceMap: {
            @foreach($durations as $duration)
                "{{ $duration->duration_hour }}": {{ $duration->price }},
            @endforeach
        },
        durationMap: {
             @foreach($durations as $duration)
                "{{ $duration->duration_hour }}": "{{ $duration->label }}",
            @endforeach
        },
        socksPrice: {{ $items['socks_price']}},
        promoCodes: @json(\App\Enums\PromoCode::options()),
        extras: {
            myFacebookPage: 'https://www.facebook.com/mimoplaycafe',
            termsAndAgreementPage: 'https://docs.google.com/document/d/1HVJZN-q5mSyOsgh_BHlsKVo9ErGVmh8etg33zvyusmM/edit?fbclid=IwY2xjawRJgTpleHRuA2FlbQIxMQBzcnRjBmFwcF9pZA80Mzc2MjYzMTY5NzM3ODgAAR7vCcMrWACR-uB7HwsD__XyE4L04JPjBwUugFhipxjOdq1sc7cMia7aMbh43Q_aem_vRdMreCk6yhki-xqNnDx2w&tab=t.0'
        }
    }
</script>