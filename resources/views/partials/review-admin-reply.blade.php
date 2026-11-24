{{-- Brand reply under a customer review (point 17). Expects $review. Prints nothing when there is no reply. --}}
@php $adminReply = trim((string) ($review->admin_reply ?? '')); @endphp
@if ($adminReply !== '')
    <div class="review-admin-reply" style="margin:10px 0 0;padding:10px 12px;border-left:3px solid var(--pk, #ff4d8f);background:#f8fafc;border-radius:8px;">
        <div style="font-size:12px;font-weight:800;color:var(--pk, #ff4d8f);margin-bottom:2px;">Reply from NutriBuddy</div>
        <p style="margin:0;font-size:13px;line-height:1.5;color:#475569;">{{ $adminReply }}</p>
    </div>
@endif