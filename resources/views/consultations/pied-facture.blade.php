<div @if(isset($class)) class="{{ $class }}" @endif style="width: 100%; margin-top: 30px;">
    <div style="text-align:center;border-top:1px solid #cbd5e1;padding:10px;color:#64748b">{{ $cabinetBrand['name'] }}@if($cabinetBrand['email']) · {{ $cabinetBrand['email'] }}@endif</div>
</div>
