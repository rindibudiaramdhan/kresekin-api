<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerProductRequest;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SellerProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('tenant')
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->latest()
            ->paginate(10);

        return view('seller.products.index', [
            'products' => $products,
        ]);
    }

    public function create(Request $request): View
    {
        return view('seller.products.form', [
            'product' => new Product(),
            'tenants' => $request->user()->ownedTenants()->latest()->get(),
            'categories' => Tenant::CATEGORIES,
        ]);
    }

    public function store(SellerProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated());

        return redirect()
            ->route('seller.products.index')
            ->with('status', 'Produk berhasil dibuat.');
    }

    public function edit(Request $request, int $id): View
    {
        $product = Product::query()
            ->with('tenant')
            ->where('id', $id)
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->firstOrFail();

        return view('seller.products.form', [
            'product' => $product,
            'tenants' => $request->user()->ownedTenants()->latest()->get(),
            'categories' => Tenant::CATEGORIES,
        ]);
    }

    public function update(SellerProductRequest $request, int $id): RedirectResponse
    {
        $product = Product::query()
            ->where('id', $id)
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->firstOrFail();

        $product->update($request->validated());

        return redirect()
            ->route('seller.products.index')
            ->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $product = Product::query()
            ->where('id', $id)
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->firstOrFail();

        $product->delete();

        return redirect()
            ->route('seller.products.index')
            ->with('status', 'Produk berhasil dihapus.');
    }
}
