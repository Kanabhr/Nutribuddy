@extends('layouts.main')

@section('content')
<div style="padding: 40px; font-family: monospace; background: #f5f5f5;">
    <h2>🔐 Authorization Debug Check</h2>
    
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3>Current User</h3>
        @auth
            <p><strong>User ID:</strong> {{ Auth::id() }}</p>
            <p><strong>User Email:</strong> {{ Auth::user()->email }}</p>
            <p><strong>Status:</strong> ✅ Logged In</p>
        @else
            <p><strong>Status:</strong> ❌ Not Logged In</p>
            <p><a href="{{ route('frontend.login') }}">→ Login</a></p>
        @endauth
    </div>

    @auth
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Your Children</h3>
            @if(Auth::user()->children->count() > 0)
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background: #f0f0f0;">
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Child ID</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Name</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">User ID</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Assessment</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Diet Plan</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Action</th>
                    </tr>
                    @foreach(Auth::user()->children as $child)
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;">{{ $child->id }}</td>
                            <td style="border: 1px solid #ddd; padding: 10px;">{{ $child->name }}</td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                {{ $child->user_id }}
                                @if($child->user_id === Auth::id())
                                    ✅ (Matches)
                                @else
                                    ❌ (Mismatch!)
                                @endif
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                @if($child->assessments()->count() > 0)
                                    ✅ Yes ({{ $child->assessments()->count() }})
                                @else
                                    ❌ No
                                @endif
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                @php
                                    $assessment = $child->assessments()->latest()->first();
                                    $plan = $assessment ? $assessment->dietPlans()->where('status', 'active')->count() : 0;
                                @endphp
                                @if($plan > 0)
                                    ✅ Active ({{ $plan }})
                                @else
                                    ⚠️ None
                                @endif
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                <a href="{{ route('child.diet-chart.show', $child) }}" 
                                   style="color: #7B2CBF; text-decoration: none;">
                                   → View Diet Chart
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p>❌ You haven't created any child profiles yet.</p>
                <p><a href="{{ route('child-profile') }}">→ Create Child Profile</a></p>
            @endif
        </div>

        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Quick Actions</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="{{ route('child-profile') }}" style="color: #7B2CBF; text-decoration: none;">
                        → Go to Child Profile
                    </a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="{{ route('frontend.logout') }}" style="color: #D81C7F; text-decoration: none;">
                        → Logout
                    </a>
                </li>
            </ul>
        </div>

        <div style="background: #fffbe6; padding: 20px; border-radius: 8px; border-left: 4px solid #FFD600;">
            <h3>✅ What to Check</h3>
            <ul>
                <li>✅ User ID matches with your children's user_id column</li>
                <li>✅ Each child has at least one Assessment (✅ Yes)</li>
                <li>✅ At least one child has an Active Diet Plan</li>
                <li>✅ Click "View Diet Chart" for any child to see the meal plan</li>
            </ul>
        </div>
    @endauth
</div>

<style>
    a {
        color: #7B2CBF;
        font-weight: bold;
    }
    a:hover {
        color: #5A189A;
    }
</style>
@endsection
