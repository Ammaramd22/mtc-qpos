<?php

namespace App\Http\Controllers;

use App\Size;
use App\Utils\ProductSizeColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SizeController extends Controller
{
    public function index()
    {
        try {
            if (! auth()->user()->can('product.view') && ! auth()->user()->can('product.create')) {
                abort(403, 'Unauthorized action.');
            }

            $sizes = $this->loadCatalogSizes();
            $product_counts = $this->productCountsFor($sizes);

            return response(view('size.manage', [
                'sizes' => $sizes,
                'product_counts' => $product_counts,
            ])->render());
        } catch (\Throwable $e) {
            try {
                \Log::emergency('Sizes page: '.$e->getMessage());
            } catch (\Throwable $ignore) {
            }

            return response($this->plainSizePage(
                isset($sizes) ? $sizes : collect(),
                isset($product_counts) && is_array($product_counts) ? $product_counts : []
            ), 200);
        }
    }

    public function show($id)
    {
        return redirect('/sizes');
    }

    public function create()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $quick_add = ! empty(request()->input('quick_add'));

        return view('size.create')->with(compact('quick_add'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $name = ProductSizeColor::cleanName($request->input('name'));
            if ($name === null) {
                return $this->sizeResult(['success' => false, 'msg' => __('size.name_required')]);
            }

            $business_id = (int) $request->session()->get('user.business_id');
            $existing = Size::where('business_id', $business_id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
            if ($existing) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('sizes', 'is_catalog') && empty($existing->is_catalog)) {
                    $existing->is_catalog = 1;
                    $existing->name = $name;
                    $existing->save();

                    return $this->sizeResult([
                        'success' => true,
                        'data' => $existing,
                        'msg' => __('size.added_success'),
                    ]);
                }

                return $this->sizeResult(['success' => false, 'msg' => __('size.duplicate')]);
            }

            $attributes = [
                'business_id' => $business_id,
                'name' => $name,
                'created_by' => $request->session()->get('user.id'),
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('sizes', 'is_catalog')) {
                $attributes['is_catalog'] = 1;
            }

            $size = Size::create($attributes);

            return $this->sizeResult([
                'success' => true,
                'data' => $size,
                'msg' => __('size.added_success'),
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            if ($this->isDuplicateKey($e)) {
                return $this->sizeResult(['success' => false, 'msg' => __('size.duplicate')]);
            }

            return $this->sizeResult(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function edit($id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $size = Size::catalogQuery($business_id)->findOrFail($id);

            return view('size.edit')->with(compact('size'));
        }
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $name = ProductSizeColor::cleanName($request->input('name'));
            if ($name === null) {
                return $this->sizeResult(['success' => false, 'msg' => __('size.name_required')]);
            }

            $business_id = (int) $request->session()->get('user.business_id');
            $size = Size::catalogQuery($business_id)->findOrFail($id);

            if (ProductSizeColor::nameTaken($business_id, $name, (int) $size->id)) {
                return $this->sizeResult(['success' => false, 'msg' => __('size.duplicate')]);
            }

            $size->name = $name;
            $size->save();
            ProductSizeColor::refreshProductsForSize($size);

            return $this->sizeResult(['success' => true, 'msg' => __('size.updated_success')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            if ($this->isDuplicateKey($e)) {
                return $this->sizeResult(['success' => false, 'msg' => __('size.duplicate')]);
            }

            return $this->sizeResult(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $size = Size::catalogQuery($business_id)->findOrFail($id);

            $inUse = DB::table('product_size')->where('size_id', $size->id)->exists();
            if ($inUse) {
                return $this->sizeResult(['success' => false, 'msg' => __('size.delete_blocked')]);
            }

            $size->delete();

            return $this->sizeResult(['success' => true, 'msg' => __('size.deleted_success')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->sizeResult(['success' => false, 'msg' => __('size.delete_blocked')]);
        }
    }

    /**
     * Modal saves stay as JSON. A normal browser submit returns to the Sizes page.
     *
     * @param  array<string, mixed>  $output
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|array<string, mixed>
     */
    private function sizeResult(array $output)
    {
        if (request()->ajax()) {
            return $output;
        }

        return redirect('/sizes')->with('status', [
            'success' => ! empty($output['success']) ? 1 : 0,
            'msg' => $output['msg'] ?? '',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Size>
     */
    private function loadCatalogSizes()
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            return Size::where('business_id', $business_id)
                ->where('is_catalog', 1)
                ->orderBy('name')
                ->get(['id', 'name']);
        } catch (\Throwable $e) {
            \Log::emergency('Sizes load: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Size>  $sizes
     * @return array<int, int>
     */
    private function productCountsFor($sizes): array
    {
        $counts = [];
        try {
            if ($sizes->isEmpty()) {
                return $counts;
            }

            $rows = DB::table('product_size')
                ->whereIn('size_id', $sizes->pluck('id')->all())
                ->selectRaw('size_id, COUNT(*) as total')
                ->groupBy('size_id')
                ->get();
            foreach ($rows as $row) {
                $counts[(int) $row->size_id] = (int) $row->total;
            }
        } catch (\Throwable $e) {
            \Log::emergency('Size product counts: '.$e->getMessage());
        }

        return $counts;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Size>  $sizes
     * @param  array<int, int>  $product_counts
     */
    private function plainSizePage($sizes, array $product_counts): string
    {
        $rows = '';
        foreach ($sizes as $size) {
            $count = (int) ($product_counts[$size->id] ?? 0);
            $name = htmlspecialchars((string) $size->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $rows .= '<tr><td>'.$name.'</td><td>'.$count.'</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="2">No sizes yet. Use Add on the Sizes page after refreshing.</td></tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Sizes</title></head><body style="font-family:sans-serif;padding:24px"><h1>Sizes</h1><p><a href="/sizes">Reload</a></p><table border="1" cellpadding="8" cellspacing="0"><tr><th>Size name</th><th>Products</th></tr>'.$rows.'</table></body></html>';
    }

    private function isDuplicateKey(\Exception $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Integrity constraint violation')
            || str_contains($message, '1062');
    }
}
