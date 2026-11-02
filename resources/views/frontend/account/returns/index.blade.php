@extends('layouts.user-panel')
@section('title', 'My Returns — NutriBuddy Kids')
@section('panel-page-class', 'panel-returns')
@section('panel-content')
    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Return History ↩️</span>
        <div style="width:36px"></div>
    </div>

    <div class="page">
        @if(session('success'))
            <div class="alert alert-success" style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                {{ session('error') }}
            </div>
        @endif

        <div class="fade-in d1">
            @forelse($returns as $return)
                <div class="return-item-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                                <span style="font-family: 'Fredoka One', cursive; font-size: 1.3rem; color: var(--dk);">#{{ $return->return_number }}</span>
                                <span class="status-badge {{ $return->status === 'completed' ? 's-delivered' : ($return->status === 'rejected' ? 's-cancelled' : 's-pending') }}" style="font-size: 0.75rem; padding: 6px 14px; border-radius: 50px; font-weight: 800; letter-spacing: 0.5px;">
                                    {{ strtoupper($return->status) }}
                                </span>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <p style="margin: 0; color: #555; font-size: 0.95rem; font-weight: 600;">
                                    Reference Order: <a href="{{ route('user.orders.detail-page', $return->order_id) }}" style="color: var(--pk); text-decoration: none; border-bottom: 1px dashed var(--pk);">#{{ $return->order->order_number }}</a>
                                </p>
                                <p style="margin: 0; font-size: 0.85rem; color: #999; display: flex; align-items: center; gap: 5px;">
                                    <span>📅</span> Requested on {{ $return->created_at->format('d M Y, h:i A') }}
                                </p>
                            </div>
                        </div>
                        <div style="text-align: right; background: var(--cr); padding: 12px 20px; border-radius: 15px; border: 1.5px solid var(--border);">
                            <div style="font-family: 'Fredoka One', cursive; color: var(--pk); font-size: 1.4rem; margin-bottom: 2px;">
                                ₹{{ number_format($return->refund_amount, 2) }}
                            </div>
                            <span style="font-size: 0.7rem; color: #888; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Refund Amount</span>
                        </div>
                    </div>

                    <div style="background: #fdfdfd; border-radius: 18px; padding: 20px; margin-bottom: 20px; border: 1.5px solid var(--border); position: relative;">
                        <div style="margin-bottom: 15px;">
                            <strong style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: #aaa; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">
                                <span>💬</span> Reason & Comments
                            </strong>
                            <p style="margin: 0; color: var(--dk); font-weight: 700; font-size: 1rem; line-height: 1.5;">{{ $return->reason }}</p>
                        </div>
                        
                        @if($return->admin_note)
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee;">
                                <strong style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--mn); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">
                                    <span>🛡️</span> Admin Response
                                </strong>
                                <p style="margin: 0; color: #444; font-size: 0.95rem; font-weight: 500; font-style: italic; background: #fff; padding: 10px; border-radius: 10px; border-left: 4px solid var(--mn);">{{ $return->admin_note }}</p>
                            </div>
                        @endif
                    </div>

                    @if($return->media_paths && count($return->media_paths) > 0)
                        <div style="margin-top: 20px;">
                            <strong style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: #aaa; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px;">
                                <span>📸</span> Uploaded Media ({{ count($return->media_paths) }})
                            </strong>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                @foreach($return->media_paths as $path)
                                    @php
                                        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                        $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'wmv']);
                                    @endphp
                                    <div class="media-preview" style="width: 110px; height: 110px; border-radius: 16px; overflow: hidden; border: 2.5px solid var(--border); position: relative; background: #fafafa;">
                                        @if($isVideo)
                                            <video src="{{ asset('storage/' . $path) }}" style="width: 100%; height: 100%; object-fit: cover;"></video>
                                            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.25);">
                                                <span style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">▶️</span>
                                            </div>
                                        @else
                                            <img src="{{ asset('storage/' . $path) }}" alt="Return proof" style="width: 100%; height: 100%; object-fit: cover;" onclick="window.open('{{ asset('storage/' . $path) }}', '_blank')">
                                        @endif
                                        <a href="{{ asset('storage/' . $path) }}" target="_blank" style="position: absolute; bottom: 8px; right: 8px; background: #fff; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(0,0,0,0.15); border: 1px solid #eee;">🔍</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align: center; padding: 100px 20px; background: #fff; border-radius: 30px; border: 2.5px dashed var(--border);">
                    <div style="font-size: 6rem; margin-bottom: 30px; animation: floatY 4s ease-in-out infinite;">📦</div>
                    <h3 style="font-family: 'Fredoka One', cursive; color: var(--dk); font-size: 1.8rem; margin-bottom: 15px;">No return requests yet</h3>
                    <p style="color: #999; font-size: 1.1rem; max-width: 500px; margin: 0 auto 30px; line-height: 1.6;">If you have any issues with your products, you can request a return from your order history within 7 days of delivery.</p>
                    <a href="{{ route('order') }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px; padding: 18px 40px; border-radius: 50px; background: linear-gradient(135deg, var(--pk), var(--pkd)); color: #fff; font-weight: 900; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(255, 77, 143, 0.4); transition: transform 0.2s;">
                        <span>🛒</span> View My Orders
                    </a>
                </div>
            @endforelse

            <div style="margin-top: 40px;">
                {{ $returns->links() }}
            </div>
        </div>
    </div>
@endsection
