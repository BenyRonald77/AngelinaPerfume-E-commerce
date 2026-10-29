@php
    use App\Support\Rupiah;
    use App\Support\Waktu;
    use App\Support\WhatsApp;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label {{ $order->code }}</title>
    {{-- Halaman cetak berdiri sendiri: hitam putih, tanpa aset Vite, supaya jelas di printer thermal --}}
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font: 14px/1.4 system-ui, sans-serif; color: #000; background: #fff; }
        .label { max-width: 105mm; border: 2px solid #000; padding: 12px; }
        .row { display: flex; justify-content: space-between; gap: 12px; }
        h1 { margin: 0; font-size: 18px; }
        h2 { margin: 12px 0 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        p { margin: 0; }
        .big { font-size: 16px; font-weight: 700; }
        .code { font: 700 20px/1.2 ui-monospace, monospace; letter-spacing: .04em; }
        hr { border: 0; border-top: 1px dashed #000; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        td { padding: 3px 0; vertical-align: top; }
        td:last-child { text-align: right; white-space: nowrap; padding-left: 8px; }
        .tools { margin-bottom: 16px; }
        .tools button { font: inherit; padding: 10px 18px; min-height: 44px; cursor: pointer; }
        @media print { body { padding: 0; } .tools { display: none; } @page { size: A6; margin: 6mm; } }
    </style>
</head>
<body>
    <div class="tools"><button type="button" onclick="window.print()">Cetak</button></div>

    <div class="label">
        <div class="row">
            <h1>Angelia Perfume</h1>
            <p>{{ Waktu::tanggal($order->created_at) }}</p>
        </div>
        <p class="code">{{ $order->code }}</p>
        <p>{{ $order->shipment?->courier }}{{ $order->shipment?->service ? ' · '.$order->shipment->service : '' }} · {{ number_format($order->total_weight_grams / 1000, 2, ',', '.') }} kg</p>
        @if ($order->shipment?->tracking_number)
            <p>Resi: <strong>{{ $order->shipment->tracking_number }}</strong></p>
        @endif

        <h2>Penerima</h2>
        <p class="big">{{ $order->recipient_name }}</p>
        <p>{{ $order->phone }}</p>
        <p>{{ $order->addressOneLine() }}</p>

        <h2>Pengirim</h2>
        <p>Angelia Perfume · WhatsApp {{ WhatsApp::display() }}</p>

        <hr>
        <h2>Isi paket</h2>
        <table>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->quantity }}× {{ $item->product_name }} · {{ $item->aroma_name }}{{ $item->size_label ? ' · '.$item->size_label : '' }}{{ $item->is_gift ? ' (hadiah)' : '' }}<br><small>{{ $item->sku }}</small></td>
                    <td>{{ $item->is_gift ? 'Rp0' : Rupiah::format($item->price * $item->quantity) }}</td>
                </tr>
            @endforeach
            @if ($order->packaging_fee)
                <tr><td>Extra dus dan bubble wrap</td><td>{{ Rupiah::format($order->packaging_fee) }}</td></tr>
            @endif
        </table>
        @if ($order->customer_note)
            <hr>
            <p><strong>Catatan pembeli:</strong> {{ $order->customer_note }}</p>
        @endif
    </div>
</body>
</html>
