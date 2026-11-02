<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CRM\CustomerRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(Request $request)
    {
        $query = User::where('role', 'customer')->with('customerAddresses');

        if ($request->filled('city')) {
            $query->whereHas('customerAddresses', function ($q) use ($request) {
                $q->where('city', 'like', '%' . $request->city . '%');
            });
        }

        if ($request->filled('state')) {
            $query->whereHas('customerAddresses', function ($q) use ($request) {
                $q->where('state', 'like', '%' . $request->state . '%');
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->get();
            
        return view('admin.crm.customers.index', compact('customers'));
    }

    public function store(CustomerRequest $request)
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->back()->with('success', 'Customer created successfully.');
    }

    public function update(CustomerRequest $request, User $customer)
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $customer->update($data);

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(User $customer)
    {
        if ($customer->role !== 'customer') {
            return redirect()->back()->with('error', 'Cannot delete admin users.');
        }

        $customer->delete();

        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }
}
