<?php

namespace App\Models;

/**
 * Compatibility model representing a marketplace listing / offer.
 * In Phase 2, marketplace listings were designated as Product.
 * In Phase 3, CanonicalProduct represents the master product entity,
 * while Product / ProductListing represents specific marketplace offers.
 */
class Product extends ProductListing
{
    protected $table = 'product_listings';
}
