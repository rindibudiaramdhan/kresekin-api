<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgentTenantRequest;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AgentTenantController extends Controller
{
    public function index(Request $request): View
    {
        $selectedSeller = $request->filled('seller') ? (int) $request->query('seller') : null;
        $selectedCategory = $request->filled('category') ? (string) $request->query('category') : null;
        $agentId = $request->user()->id;

        $query = Tenant::query()
            ->with('owner')
            ->where('agent_user_id', $agentId)
            ->when($selectedSeller, fn ($query) => $query->where('owner_user_id', $selectedSeller))
            ->when($selectedCategory, fn ($query) => $query->where('category', $selectedCategory))
            ->latest();

        $tenants = $query->paginate(10)->withQueryString();

        return view('agent.tenants.index', [
            'tenants' => $tenants,
            'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(),
            'categories' => Tenant::CATEGORIES,
            'selectedSeller' => $selectedSeller,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function create(): View
    {
        return view('agent.tenants.form', [
            'tenant' => new Tenant(),
            'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(),
            'categories' => Tenant::CATEGORIES,
            'ownerMode' => old('owner_mode', 'existing'),
        ]);
    }

    public function store(AgentTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $ownerUserId = $validated['owner_user_id'] ?? null;

        if (($validated['owner_mode'] ?? 'existing') === 'new') {
            $seller = User::query()->create([
                'name' => $validated['seller_name'],
                'email' => $validated['seller_email'],
                'phone' => null,
                'type' => User::AUTH_TYPE_EMAIL,
                'role' => User::ROLE_SELLER,
                'password' => Hash::make($validated['seller_password']),
                'otp_code' => null,
                'otp_sent_at' => null,
            ]);

            $ownerUserId = $seller->id;
        }

        Tenant::query()->create([
            'agent_user_id' => $request->user()->id,
            'owner_user_id' => $ownerUserId,
            'name' => $validated['name'],
            'profile_picture_url' => $validated['profile_picture_url'] ?? null,
            'rating' => $validated['rating'] ?? 0,
            'category' => $validated['category'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'open_time' => $validated['open_time'] ?? null,
            'close_time' => $validated['close_time'] ?? null,
        ]);

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil dibuat.');
    }

    public function edit(int $id): View
    {
        $tenant = Tenant::query()
            ->where('agent_user_id', auth()->id())
            ->findOrFail($id);

        return view('agent.tenants.form', [
            'tenant' => $tenant,
            'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(),
            'categories' => Tenant::CATEGORIES,
            'ownerMode' => 'existing',
        ]);
    }

    public function show(int $id): View
    {
        $tenant = Tenant::query()
            ->with(['owner', 'products'])
            ->where('agent_user_id', auth()->id())
            ->findOrFail($id);

        return view('agent.tenants.show', [
            'tenant' => $tenant,
            'products' => $tenant->products()->latest()->limit(10)->get(),
        ]);
    }

    public function update(AgentTenantRequest $request, int $id): RedirectResponse
    {
        $tenant = Tenant::query()
            ->where('agent_user_id', $request->user()->id)
            ->findOrFail($id);
        $validated = $request->validated();
        $ownerUserId = $validated['owner_user_id'] ?? $tenant->owner_user_id;

        if (($validated['owner_mode'] ?? 'existing') === 'new') {
            $seller = User::query()->create([
                'name' => $validated['seller_name'],
                'email' => $validated['seller_email'],
                'phone' => null,
                'type' => User::AUTH_TYPE_EMAIL,
                'role' => User::ROLE_SELLER,
                'password' => Hash::make($validated['seller_password']),
                'otp_code' => null,
                'otp_sent_at' => null,
            ]);

            $ownerUserId = $seller->id;
        }

        $tenant->update([
            'owner_user_id' => $ownerUserId,
            'name' => $validated['name'],
            'profile_picture_url' => $validated['profile_picture_url'] ?? null,
            'rating' => $validated['rating'] ?? 0,
            'category' => $validated['category'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'open_time' => $validated['open_time'] ?? null,
            'close_time' => $validated['close_time'] ?? null,
        ]);

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Tenant::query()
            ->where('agent_user_id', auth()->id())
            ->findOrFail($id)
            ->delete();

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil dihapus.');
    }
}
