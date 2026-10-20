<?php

/**
 * The photos each shop folder needs (one per color; file name without the
 * extension — .jpg, .jpeg, .png or .webp are all fine). Same list as the
 * "BoomBuy Catalog Plan" doc. `php artisan boombuy:catalog-check` compares
 * the folders against it and rewrites each folder's FILES.txt.
 *
 * 'done' = photos already in use on the site (not counted as missing or wrong).
 */
return [
    'voltique' => [
        'shop' => 'Voltique Electronics',
        'done' => ['iphone-18-pro-black', 'iphone-18-pro-silver', 'iphone-18-pro-glacier', 'iphone-18-pro-burgundy', 'iphone-18-pro-2'],
        'photos' => [
            'nova-x5-midnight', 'nova-x5-ocean', 'nova-x5-lavender', 'nova-x5-mint', 'nova-lite-black', 'nova-lite-silver',
            'pulse-pro-max', 'rugged-phone', 'charger-20w-white', 'charger-20w-black', 'charger-bundle',
            'cable-black', 'cable-white', 'cable-red', 'powerbank-white', 'powerbank-black', 'powerbank-pink', 'wireless-pad',
            'earbuds-white', 'earbuds-black', 'headphones-black', 'headphones-brown', 'headphones-pink', 'wired-earphones',
            'speaker-red', 'speaker-blue', 'speaker-black', 'smart-speaker-charcoal', 'smart-speaker-chalk',
        ],
    ],
    'maisonbelle' => [
        'shop' => 'Maison Belle',
        'photos' => [
            'linen-blouse-white', 'linen-blouse-beige', 'linen-blouse-sage', 'linen-blouse-pink',
            'peasant-blouse-taupe', 'peasant-blouse-black', 'peasant-blouse-wine',
            'crew-tee-white', 'crew-tee-black', 'crew-tee-grey', 'crew-tee-navy', 'crew-tee-red',
            'crop-top-black', 'crop-top-white', 'crop-top-brown', 'graphic-tee-black', 'graphic-tee-white', 'floral-dress-white', 'floral-dress-blue', 'floral-dress-teal',
            'satin-slip-black', 'satin-slip-champagne', 'satin-slip-emerald', 'aline-dress-black', 'aline-dress-wine',
            'sundress-white', 'sundress-yellow', 'sundress-blue', 'shirt-dress-beige', 'shirt-dress-black', 'shirt-dress-red',
            'satchel-tan', 'satchel-black', 'satchel-cream', 'crossbody-red', 'crossbody-black', 'crossbody-pink',
            'straw-clutch-white', 'straw-clutch-gold', 'straw-clutch-black', 'messenger-brown', 'messenger-black', 'canvas-tote-olive',
        ],
    ],
    'gentryandco' => [
        'shop' => 'Gentry & Co. Menswear',
        'photos' => [
            'oxford-shirt-white', 'oxford-shirt-light-blue', 'oxford-shirt-pink',
            'polo-shirt-black', 'polo-shirt-white', 'polo-shirt-navy', 'polo-shirt-red',
            'crew-tee-white', 'crew-tee-black', 'crew-tee-grey', 'crew-tee-olive',
            'linen-shirt-white', 'linen-shirt-beige', 'linen-shirt-sage', 'barong',
            'chino-pants-khaki', 'chino-pants-navy', 'chino-pants-black', 'jeans', 'jogger-pants-black', 'jogger-pants-grey',
            'cargo-shorts-khaki', 'cargo-shorts-olive', 'cargo-shorts-black', 'slacks-black', 'slacks-navy',
            'denim-jacket', 'hoodie-black', 'hoodie-grey', 'hoodie-navy', 'bomber-jacket-black', 'bomber-jacket-olive',
            'windbreaker-black', 'windbreaker-navy', 'cardigan-grey', 'cardigan-brown',
        ],
    ],
    'littlehaven' => [
        'shop' => 'Little Haven Baby & Kids',
        'photos' => [
            'onesie-set', 'romper-pink', 'romper-blue', 'romper-yellow', 'mittens-booties-pink', 'mittens-booties-blue', 'swaddle', 'bib-set',
            'baby-bottle', 'feeding-set-pink', 'feeding-set-blue', 'feeding-set-green', 'sippy-cup-pink', 'sippy-cup-blue',
            'breast-pump', 'sterilizer', 'baby-wipes', 'diaper-bag-black', 'diaper-bag-grey', 'diaper-bag-pink',
            'baby-bathtub-blue', 'baby-bathtub-pink', 'hooded-towel', 'changing-pad',
        ],
    ],
    'hearthstone' => [
        'shop' => 'Hearthstone Home & Living',
        'photos' => [
            'frying-pan', 'cookware-set', 'plate-set-white', 'plate-set-beige', 'food-containers', 'kettle-silver', 'kettle-black',
            'bed-sheet-white', 'bed-sheet-grey', 'bed-sheet-blue', 'bed-sheet-beige', 'pillow',
            'blanket-grey', 'blanket-beige', 'blanket-pink', 'bath-towel-white', 'bath-towel-grey', 'bath-towel-blue', 'shower-curtain',
            'scented-candle', 'vase-white', 'vase-beige', 'wall-clock-black', 'wall-clock-white', 'table-lamp',
            'throw-pillow-beige', 'throw-pillow-green', 'throw-pillow-mustard',
        ],
    ],
    'summitactive' => [
        'shop' => 'Summit Active Gear',
        'photos' => [
            'yoga-mat-purple', 'yoga-mat-blue', 'yoga-mat-black', 'yoga-mat-pink', 'dumbbells', 'resistance-bands',
            'jump-rope-black', 'jump-rope-blue', 'foam-roller-black', 'foam-roller-blue',
            'basketball', 'volleyball', 'badminton-set', 'soccer-ball', 'ping-pong-set',
            'tent-green', 'tent-orange', 'hiking-backpack-black', 'hiking-backpack-green', 'hiking-backpack-red',
            'water-bottle-black', 'water-bottle-white', 'water-bottle-blue', 'water-bottle-pink',
            'lantern', 'sleeping-bag-blue', 'sleeping-bag-green',
        ],
    ],
    'lumierebeauty' => [
        'shop' => 'Lumière Beauty',
        'photos' => [
            'cleanser', 'moisturizer', 'sunscreen', 'serum', 'sheet-mask',
            'lipstick-red', 'lipstick-nude', 'lipstick-pink', 'lipstick-brown', 'cushion-light', 'cushion-medium',
            'eyeshadow', 'mascara', 'brush-set', 'shampoo-set', 'hair-dryer-black', 'hair-dryer-pink',
            'body-lotion', 'body-wash', 'straightener-black', 'straightener-pink',
        ],
    ],
    'harvesttable' => [
        'shop' => 'Harvest Table Pantry',
        'photos' => [
            'dried-mangoes', 'banana-chips', 'chicharon', 'polvoron', 'mixed-nuts',
            'barako-coffee', 'calamansi-juice', 'tablea', 'green-tea', 'coconut-water',
            'rice', 'coconut-oil', 'honey', 'peanut-butter', 'bagoong',
        ],
    ],
    'torqueauto' => [
        'shop' => 'Torque Auto Supply',
        'photos' => [
            'car-shampoo', 'microfiber-towel', 'tire-shine', 'car-wax', 'dashboard-cleaner', 'phone-holder',
            'seat-cover-black', 'seat-cover-grey', 'seat-cover-beige', 'air-freshener',
            'steering-cover-black', 'steering-cover-brown', 'steering-cover-pink', 'floor-mats-black', 'floor-mats-grey',
            'tire-inflator', 'jumper-cables', 'dash-cam', 'engine-oil', 'wiper-blades',
        ],
    ],
    'inkwell' => [
        'shop' => 'Inkwell Office & School',
        'photos' => [
            'ballpen-set', 'mechanical-pencil', 'highlighters', 'marker', 'gel-pens',
            'notebook-blue', 'notebook-pink', 'notebook-green', 'notebook-black', 'bond-paper', 'sticky-notes', 'index-cards',
            'planner-black', 'planner-pink', 'planner-beige', 'school-backpack-black', 'school-backpack-navy', 'school-backpack-pink',
            'calculator', 'stapler', 'scissors', 'desk-organizer',
        ],
    ],
    'pawsandwhiskers' => [
        'shop' => 'Paws & Whiskers Pet Co.',
        'photos' => [
            'dog-food', 'collar-red', 'collar-blue', 'collar-black', 'leash-blue', 'leash-pink',
            'dog-bed-grey', 'dog-bed-brown', 'dog-shampoo', 'cat-food', 'wet-cat-food', 'cat-litter',
            'scratching-post', 'teaser-wand', 'litter-scoop', 'pet-bowl', 'pet-carrier', 'grooming-brush',
            'travel-bottle',
        ],
    ],
    'wonderbox' => [
        'shop' => 'Wonderbox Toys & Hobbies',
        'photos' => [
            'teddy-bear-brown', 'teddy-bear-white', 'building-blocks', 'rc-car-red', 'rc-car-blue', 'wooden-puzzle', 'toy-kitchen',
            'chess', 'dominoes', 'playing-cards', 'snakes-ladders', 'sungka',
            'speed-cube', 'watercolor', 'model-kit', 'ukulele-natural', 'ukulele-pink', 'ukulele-blue', 'sketchbook',
        ],
    ],
    'aurelia' => [
        'shop' => 'Aurelia Jewelry & Accessories',
        'photos' => [
            'pearl-earrings', 'chain-necklace-gold', 'chain-necklace-silver', 'hoop-earrings-gold', 'hoop-earrings-silver',
            'heart-pendant-gold', 'heart-pendant-silver', 'heart-pendant-rose-gold', 'drop-earrings',
            'silver-ring', 'charm-bracelet', 'beaded-bracelet-pink', 'beaded-bracelet-blue', 'beaded-bracelet-black',
            'bangles-gold', 'bangles-silver', 'couple-rings',
            'wristwatch-black', 'wristwatch-brown', 'wristwatch-silver', 'sunglasses-black', 'sunglasses-brown',
            'leather-belt-black', 'leather-belt-brown', 'hair-clips', 'wallet-black', 'wallet-brown',
        ],
    ],
    'stridefootwear' => [
        'shop' => 'Stride Footwear Co.',
        'photos' => [
            'canvas-sneakers-white', 'canvas-sneakers-black', 'canvas-sneakers-red',
            'running-shoes-black', 'running-shoes-grey', 'running-shoes-blue', 'slip-on-black', 'slip-on-white',
            'high-top-white', 'high-top-black', 'kids-sneakers-pink', 'kids-sneakers-blue',
            'oxford-shoes-black', 'oxford-shoes-brown', 'pumps-black', 'pumps-nude', 'loafers-black', 'loafers-brown',
            'doll-shoes-black', 'doll-shoes-beige', 'school-shoes',
            'slides-black', 'slides-white', 'strap-sandals-black', 'strap-sandals-brown',
            'flip-flops-black', 'flip-flops-blue', 'flip-flops-pink', 'wedge-sandals-black', 'wedge-sandals-beige',
            'house-slippers-grey', 'house-slippers-pink',
        ],
    ],
    'ironclad' => [
        'shop' => 'Ironclad Hardware & Tools',
        'photos' => [
            'hammer', 'screwdriver-set', 'wrench', 'pliers', 'hex-key-set', 'utility-knife',
            'cordless-drill', 'air-compressor', 'work-light', 'extension-cord', 'measuring-tape', 'spirit-level',
            'safety-goggles', 'work-gloves', 'toolbox',
        ],
    ],
    'evergreen' => [
        'shop' => 'Evergreen Garden Supply',
        'photos' => [
            'snake-plant', 'seeds-pack', 'succulents', 'pothos', 'calamansi-seedling',
            'clay-pot', 'hanging-planter-white', 'hanging-planter-black', 'self-watering-pot-white', 'self-watering-pot-grey',
            'seedling-tray', 'ceramic-planter-white', 'ceramic-planter-grey', 'ceramic-planter-green',
            'garden-hose', 'watering-can-green', 'watering-can-red', 'pruning-shears', 'garden-gloves', 'fertilizer',
        ],
    ],
];
