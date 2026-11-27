<div style="width: 100%; margin-bottom: 10px;">
    <div style="text-align:center;border-bottom:2px solid {{ $cabinetBrand['primary_color'] }};padding:12px 0">
        <strong style="font-size:22px;color:{{ $cabinetBrand['primary_color'] }}">{{ $cabinetBrand['name'] }}</strong>
        @if($cabinetBrand['address'])<div>{{ $cabinetBrand['address'] }}</div>@endif
        @if($cabinetBrand['phone'])<div>{{ $cabinetBrand['phone'] }}</div>@endif
    </div>
</div>
