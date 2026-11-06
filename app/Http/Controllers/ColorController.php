<?php

namespace App\Http\Controllers;

use App\Color;
use App\Utils\ProductSizeColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ColorController extends Controller
{
    public function index()
    {
        try {
            if (! auth()->user()->can('product.view') && ! auth()->user()->can('product.create')) {
                abort(403, 'Unauthorized action.');
            }

            $colors = $this->loadCatalogColors();
            $product_counts = $this->productCountsFor($colors);

            return response(view('color.manage', [
                'colors' => $colors,
                'product_counts' => $product_counts,
            ])->render());
        } catch (\Throwable $e) {
            try {
                \Log::emergency('Colors page: '.$e->getMessage());
            } catch (\Throwable $ignore) {
            }

            return response($this->plainColorPage(
                isset($colors) ? $colors : collect(),
                isset($product_counts) && is_array($product_counts) ? $product_counts : []
            ), 200);
        }
    }

    public function show($id)
    {
        return redirect('/colors');
    }

    public function create()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $quick_add = ! empty(request()->input('quick_add'));

        return view('color.create')->with(compact('quick_add'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $name = ProductSizeColor::cleanName($request->input('name'));
            if ($name === null) {
                return $this->colorResult(['success' => false, 'msg' => __('color.name_required')]);
            }

            $business_id = (int) $request->session()->get('user.business_id');
            $existing = Color::where('business_id', $business_id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
            if ($existing) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('colors', 'is_catalog') && empty($existing->is_catalog)) {
                    $existing->is_catalog = 1;
                    $existing->name = $name;
                    $existing->save();

                    return $this->colorResult([
                        'success' => true,
                        'data' => $existing,
                        'msg' => __('color.added_success'),
                    ]);
                }

                return $this->colorResult(['success' => false, 'msg' => __('color.duplicate')]);
            }

            $attributes = [
                'business_id' => $business_id,
                'name' => $name,
                'created_by' => $request->session()->get('user.id'),
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('colors', 'is_catalog')) {
                $attributes['is_catalog'] = 1;
            }

            $color = Color::create($attributes);

            return $this->colorResult([
                'success' => true,
                'data' => $color,
                'msg' => __('color.added_success'),
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            if ($this->isDuplicateKey($e)) {
                return $this->colorResult(['success' => false, 'msg' => __('color.duplicate')]);
            }

            return $this->colorResult(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function edit($id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $color = Color::catalogQuery($business_id)->findOrFail($id);

            return view('color.edit')->with(compact('color'));
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
                return $this->colorResult(['success' => false, 'msg' => __('color.name_required')]);
            }

            $business_id = (int) $request->session()->get('user.business_id');
            $color = Color::catalogQuery($business_id)->findOrFail($id);

            if (ProductSizeColor::colorNameTaken($business_id, $name, (int) $color->id)) {
                return $this->colorResult(['success' => false, 'msg' => __('color.duplicate')]);
            }

            $color->name = $name;
            $color->save();
            ProductSizeColor::refreshProductsForColor($color);

            return $this->colorResult(['success' => true, 'msg' => __('color.updated_success')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            if ($this->isDuplicateKey($e)) {
                return $this->colorResult(['success' => false, 'msg' => __('color.duplicate')]);
            }

            return $this->colorResult(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $color = Color::catalogQuery($business_id)->findOrFail($id);

            $inUse = DB::table('product_color')->where('color_id', $color->id)->exists();
            if ($inUse) {
                return $this->colorResult(['success' => false, 'msg' => __('color.delete_blocked')]);
            }

            $color->delete();

            return $this->colorResult(['success' => true, 'msg' => __('color.deleted_success')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->colorResult(['success' => false, 'msg' => __('color.delete_blocked')]);
        }
    }

    /**
     * Modal saves stay as JSON. A normal browser submit returns to the Colors page.
     *
     * @param  array<string, mixed>  $output
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|array<string, mixed>
     */
    private function colorResult(array $output)
    {
        if (request()->ajax()) {
            return $output;
        }

        return redirect('/colors')->with('status', [
            'success' => ! empty($output['success']) ? 1 : 0,
            'msg' => $output['msg'] ?? '',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Color>
     */
    private function loadCatalogColors()
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            return Color::where('business_id', $business_id)
                ->where('is_catalog', 1)
                ->orderBy('name')
                ->get(['id', 'name']);
        } catch (\Throwable $e) {
            \Log::emergency('Colors load: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Color>  $colors
     * @return array<int, int>
     */
    private function productCountsFor($colors): array
    {
        $counts = [];
        try {
            if ($colors->isEmpty()) {
                return $counts;
            }

            $rows = DB::table('product_color')
                ->whereIn('color_id', $colors->pluck('id')->all())
                ->selectRaw('color_id, COUNT(*) as total')
                ->groupBy('color_id')
                ->get();
            foreach ($rows as $row) {
                $counts[(int) $row->color_id] = (int) $row->total;
            }
        } catch (\Throwable $e) {
            \Log::emergency('Color product counts: '.$e->getMessage());
        }

        return $counts;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Color>  $colors
     * @param  array<int, int>  $product_counts
     */
    private function plainColorPage($colors, array $product_counts): string
    {
        $rows = '';
        foreach ($colors as $color) {
            $count = (int) ($product_counts[$color->id] ?? 0);
            $name = htmlspecialchars((string) $color->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $rows .= '<tr><td>'.$name.'</td><td>'.$count.'</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="2">No colours yet. Use Add on the Colors page after refreshing.</td></tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Colors</title></head><body style="font-family:sans-serif;padding:24px"><h1>Colors</h1><p><a href="/colors">Reload</a></p><table border="1" cellpadding="8" cellspacing="0"><tr><th>Color name</th><th>Products</th></tr>'.$rows.'</table></body></html>';
    }

    private function isDuplicateKey(\Exception $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Integrity constraint violation')
            || str_contains($message, '1062');
    }
}
