<?php

namespace App\Services;

use App\Models\ProductUnit;
use App\Models\Unit;

class UnitConversionService
{
    /**
     * Convert quantity from a given unit to base unit.
     *
     * @param  int  $productId
     * @param  int  $unitId
     * @param  float  $quantity
     * @return float  Quantity in base units
     */
    public function toBaseUnit(int $productId, int $unitId, float $quantity): float
    {
        $productUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        // If the unit_id is the product's base unit, conversion rate is 1
        if (! $productUnit) {
            $product = \App\Models\Product::findOrFail($productId);
            if ($product->base_unit_id === $unitId) {
                return $quantity; // Already in base unit
            }
            throw new \InvalidArgumentException("Unit {$unitId} is not configured for product {$productId}.");
        }

        return $quantity * (float) $productUnit->conversion_rate;
    }

    /**
     * Convert quantity from base unit to a given unit.
     *
     * @param  int  $productId
     * @param  int  $unitId
     * @param  float  $baseQuantity  Quantity in base units
     * @return float  Quantity in target unit
     */
    public function fromBaseUnit(int $productId, int $unitId, float $baseQuantity): float
    {
        $productUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        if (! $productUnit) {
            $product = \App\Models\Product::findOrFail($productId);
            if ($product->base_unit_id === $unitId) {
                return $baseQuantity;
            }
            throw new \InvalidArgumentException("Unit {$unitId} is not configured for product {$productId}.");
        }

        $rate = (float) $productUnit->conversion_rate;

        if ($rate <= 0) {
            throw new \InvalidArgumentException("Conversion rate must be positive.");
        }

        return $baseQuantity / $rate;
    }

    /**
     * Get a human-readable stock display string.
     * Example: "500 kg (10 bags)"
     *
     * @param  int  $productId
     * @param  float  $baseQuantity  Stock in base units
     * @return string
     */
    public function formatStock(int $productId, float $baseQuantity): string
    {
        $product = \App\Models\Product::with('baseUnit')->findOrFail($productId);
        $baseUnit = $product->baseUnit;

        $display = number_format($baseQuantity, 2) . ' ' . $baseUnit->short_name;

        // Find the largest sensible unit conversion for a secondary display
        $largeUnit = ProductUnit::where('product_id', $productId)
            ->where('conversion_rate', '>', 1)
            ->orderBy('conversion_rate', 'desc')
            ->with('unit')
            ->first();

        if ($largeUnit && $baseQuantity >= (float) $largeUnit->conversion_rate) {
            $converted = $baseQuantity / (float) $largeUnit->conversion_rate;
            $display .= ' (' . number_format($converted, 2) . ' ' . $largeUnit->unit->short_name . ')';
        }

        return $display;
    }
}
