<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Fill the shop with categories, their items and the items' reviews.
     */
    public function run(): void
    {
        // created_at is not mass assignable; unguarding lets the demo data have realistic dates.
        Model::unguard();

        // The demo items and reviews belong to the admin account (if it exists).
        $ownerId = User::where('role', User::ROLE_ADMIN)->value('id');

        foreach ($this->catalog() as $categoryData) {
            $categoryCreatedAt = fake()->dateTimeBetween('-200 days', '-190 days');

            $category = Category::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'created_at' => $categoryCreatedAt,
                'updated_at' => $categoryCreatedAt,
            ]);

            foreach ($categoryData['items'] as $itemData) {
                $itemCreatedAt = fake()->dateTimeBetween('-180 days', '-120 days');

                $item = $category->items()->create([
                    'user_id' => $ownerId,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'],
                    'price' => $itemData['price'],
                    'stock' => $itemData['stock'],
                    'created_at' => $itemCreatedAt,
                    'updated_at' => $itemCreatedAt,
                ]);

                foreach ($itemData['reviews'] as [$authorName, $rating, $comment]) {
                    $reviewCreatedAt = fake()->dateTimeBetween('-100 days', '-1 hour');

                    $item->reviews()->create([
                        'user_id' => $ownerId,
                        'author_name' => $authorName,
                        'rating' => $rating,
                        'comment' => $comment,
                        'created_at' => $reviewCreatedAt,
                        'updated_at' => $reviewCreatedAt,
                    ]);
                }
            }
        }

        Model::reguard();
    }

    /**
     * The demo catalog. Each review is written as [author name, rating 1-5, comment].
     *
     * @return list<array{name: string, description: string, items: list<array{name: string, description: string, price: float, stock: int, reviews: list<array{string, int, string}>}>}>
     */
    private function catalog(): array
    {
        return [
            [
                'name' => 'Electronics',
                'description' => 'Phones accessories, audio, chargers and smart gadgets for everyday use.',
                'items' => [
                    [
                        'name' => 'Wireless Bluetooth 5.3 Earbuds',
                        'description' => 'True wireless earbuds with touch controls, 30 hours of battery life with the charging case and IPX5 splash resistance.',
                        'price' => 15.99,
                        'stock' => 420,
                        'reviews' => [
                            ['Tomas K.', 5, 'Great sound for the price and the case charges over USB-C. Arrived in Vilnius after 9 days.'],
                            ['Anna W.', 4, 'Good bass and a stable connection. The touch controls are a bit too sensitive.'],
                            ['Marco R.', 3, 'Sound is fine, but the left earbud sometimes disconnects for a second.'],
                        ],
                    ],
                    [
                        'name' => '65W GaN USB-C Fast Charger',
                        'description' => 'Compact gallium nitride charger with two USB-C ports and one USB-A port. Charges a laptop and a phone at the same time.',
                        'price' => 18.49,
                        'stock' => 310,
                        'reviews' => [
                            ['Lukas P.', 5, 'Charges my MacBook Air at full speed and stays cool. Much smaller than the original charger.'],
                            ['Rūta M.', 5, 'Bought two, both work perfectly with my phone\'s fast charging.'],
                        ],
                    ],
                    [
                        'name' => 'Smart Watch with Heart Rate Monitor',
                        'description' => '1.8" AMOLED smart watch with heart rate, SpO2 and sleep tracking, 100+ sport modes and a 7-day battery.',
                        'price' => 29.90,
                        'stock' => 150,
                        'reviews' => [
                            ['Elena S.', 4, 'The screen is bright and the battery really lasts a week. The app translation is a bit odd.'],
                            ['Jonas B.', 2, 'The step counter counts steps while I am driving. Heart rate readings are not reliable.'],
                            ['Sofia L.', 4, 'Looks much more expensive than it is. Notifications work fine with Android.'],
                        ],
                    ],
                    [
                        'name' => '20000mAh Power Bank with LED Display',
                        'description' => 'High-capacity power bank with 22.5W fast charging, a battery percentage display and three outputs.',
                        'price' => 21.99,
                        'stock' => 0,
                        'reviews' => [
                            ['Mantas J.', 5, 'Charged my phone almost five times on a camping trip. The display is very handy.'],
                            ['Greta V.', 3, 'Works, but it is heavy and needs a whole night to recharge.'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Kitchen tools, lighting and small helpers for the home.',
                'items' => [
                    [
                        'name' => 'Electric Milk Frother',
                        'description' => 'Handheld battery-powered frother for lattes, cappuccinos and hot chocolate, with a stainless steel whisk.',
                        'price' => 8.99,
                        'stock' => 520,
                        'reviews' => [
                            ['Paweł Z.', 5, 'Makes perfect foam in 15 seconds. The best cheap upgrade for my morning coffee.'],
                            ['Laura D.', 4, 'Works well, but batteries are not included.'],
                        ],
                    ],
                    [
                        'name' => 'Silicone Kitchen Utensil Set (12 pcs)',
                        'description' => 'Heat-resistant silicone spatulas, spoons and tongs with wooden handles and a holder.',
                        'price' => 14.50,
                        'stock' => 230,
                        'reviews' => [
                            ['Andrius T.', 5, 'Good quality silicone with no smell at all. The wooden handles feel solid.'],
                            ['Ieva G.', 4, 'Nice set, although the ladle is smaller than I expected.'],
                            ['Daniel H.', 5, 'Safe for my non-stick pans and easy to wash. Delivery was fast.'],
                        ],
                    ],
                    [
                        'name' => 'RGB LED Strip Lights 10 m',
                        'description' => 'App and remote controlled LED strip with 16 million colours, a music sync mode and an adhesive back.',
                        'price' => 11.20,
                        'stock' => 640,
                        'reviews' => [
                            ['Marta N.', 4, 'Bright colours and the music mode is fun. The adhesive could be stronger.'],
                            ['Karolis Š.', 2, 'The remote stopped working after two weeks, only the app still works.'],
                        ],
                    ],
                    [
                        'name' => 'Stainless Steel Vacuum Water Bottle 750 ml',
                        'description' => 'Double-wall insulated bottle that keeps drinks cold for 24 hours or hot for 12 hours.',
                        'price' => 9.80,
                        'stock' => 380,
                        'reviews' => [
                            ['Olivia F.', 5, 'There was still ice in the bottle the next morning. No leaks in my bag.'],
                            ['Domantas R.', 4, 'Keeps tea hot all day. The paint scratches easily.'],
                            ['Agnė L.', 5, 'Great bottle for the gym, exactly as in the photos.'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, bags and accessories for every season.',
                'items' => [
                    [
                        'name' => 'Men\'s Oversized Cotton T-Shirt',
                        'description' => 'Loose fit T-shirt made of 100% cotton, available in eight colours.',
                        'price' => 7.99,
                        'stock' => 900,
                        'reviews' => [
                            ['Tomas K.', 4, 'Soft cotton and good stitching. Order one size smaller than usual.'],
                            ['Lukas P.', 3, 'The colour faded a little after the first wash.'],
                        ],
                    ],
                    [
                        'name' => 'Women\'s Waterproof Hooded Jacket',
                        'description' => 'Lightweight windproof and waterproof jacket with a detachable hood and taped seams.',
                        'price' => 34.00,
                        'stock' => 120,
                        'reviews' => [
                            ['Rūta M.', 5, 'Survived a whole rainy day in Kaunas and I stayed completely dry.'],
                            ['Anna W.', 4, 'Warm enough for autumn. The sleeves are a bit long.'],
                            ['Elena S.', 5, 'Excellent quality for this price, the zippers feel premium.'],
                        ],
                    ],
                    [
                        'name' => 'Polarized Sunglasses UV400',
                        'description' => 'Polarized lenses with full UV400 protection and a lightweight metal frame. Comes with a case.',
                        'price' => 5.49,
                        'stock' => 760,
                        'reviews' => [
                            ['Marco R.', 4, 'Real polarized lenses, I checked them against my phone screen. Good value.'],
                            ['Sofia L.', 1, 'One lens fell out on the second day.'],
                        ],
                    ],
                    [
                        'name' => 'Canvas Travel Backpack 30 L',
                        'description' => 'Water-resistant canvas backpack with a padded laptop pocket and a USB charging port.',
                        'price' => 22.75,
                        'stock' => 210,
                        'reviews' => [
                            ['Jonas B.', 5, 'Fits my 15-inch laptop and everything I need for a weekend trip.'],
                            ['Greta V.', 4, 'Nice canvas and lots of pockets. The USB port is a bit of a gimmick.'],
                            ['Mantas J.', 5, 'Took it on three flights already and it still looks new.'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Gear for workouts, cycling and camping.',
                'items' => [
                    [
                        'name' => 'Resistance Bands Set (5 pcs)',
                        'description' => 'Latex resistance bands in five strengths with handles, ankle straps and a door anchor.',
                        'price' => 12.30,
                        'stock' => 450,
                        'reviews' => [
                            ['Paweł Z.', 5, 'Perfect for home workouts and the handles are comfortable.'],
                            ['Laura D.', 4, 'Good set. The strongest band is really strong.'],
                        ],
                    ],
                    [
                        'name' => 'Non-Slip Yoga Mat 6 mm',
                        'description' => 'Eco-friendly TPE yoga mat with a non-slip texture on both sides and a carrying strap.',
                        'price' => 16.90,
                        'stock' => 175,
                        'reviews' => [
                            ['Ieva G.', 5, 'Thick and comfortable for my knees, and it does not slide on the floor.'],
                            ['Agnė L.', 4, 'Smelled a little at first, but the smell was gone after two days.'],
                            ['Olivia F.', 5, 'Great grip even during hot yoga.'],
                        ],
                    ],
                    [
                        'name' => 'USB Rechargeable Bike Light Set',
                        'description' => 'Front and rear LED bike lights with four modes, IPX4 water resistance and USB charging.',
                        'price' => 9.99,
                        'stock' => 330,
                        'reviews' => [
                            ['Andrius T.', 4, 'Very bright front light. The battery lasts about 3 hours on full power.'],
                            ['Daniel H.', 5, 'Easy to mount without tools. Cars definitely see me now.'],
                        ],
                    ],
                    [
                        'name' => 'Ultralight Camping Hammock',
                        'description' => 'Parachute nylon hammock for two people with tree straps. Weighs only 600 g.',
                        'price' => 19.60,
                        'stock' => 95,
                        'reviews' => [
                            ['Karolis Š.', 5, 'Set it up in two minutes and slept in it all night. Holds two adults.'],
                            ['Domantas R.', 3, 'Comfortable, but the carabiners feel cheap, so I replaced them.'],
                            ['Marta N.', 4, 'Packs into a tiny bag, ideal for hiking.'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Toys & Hobbies',
                'description' => 'Toys, puzzles and gadgets for kids and grown-ups.',
                'items' => [
                    [
                        'name' => 'Magnetic Building Tiles (64 pcs)',
                        'description' => 'Colourful magnetic tiles for building 3D shapes, with safe rounded edges. For ages 3+.',
                        'price' => 27.40,
                        'stock' => 140,
                        'reviews' => [
                            ['Laura D.', 5, 'My kids play with them every day. The magnets are strong.'],
                            ['Tomas K.', 4, 'Good quality and compatible with the expensive brand we already had.'],
                        ],
                    ],
                    [
                        'name' => 'Remote Control Stunt Car 2.4 GHz',
                        'description' => 'Double-sided stunt car that flips and spins 360°, with a rechargeable battery.',
                        'price' => 24.99,
                        'stock' => 85,
                        'reviews' => [
                            ['Jonas B.', 4, 'Fast and survives crashes into furniture. The battery lasts about 20 minutes.'],
                            ['Sofia L.', 5, 'Birthday present for my nephew and he loves it.'],
                            ['Mantas J.', 2, 'One wheel broke after a week.'],
                        ],
                    ],
                    [
                        'name' => '1000-Piece Jigsaw Puzzle "Night City"',
                        'description' => 'Puzzle with a city skyline at night, made of thick cardboard pieces. 70 × 50 cm when finished.',
                        'price' => 10.50,
                        'stock' => 260,
                        'reviews' => [
                            ['Rūta M.', 5, 'The pieces fit tightly and the print quality is excellent.'],
                            ['Elena S.', 4, 'Challenging and fun, it took us two weekends.'],
                        ],
                    ],
                    [
                        'name' => 'Mini Drone with 1080p Camera',
                        'description' => 'Foldable mini drone with a 1080p camera, altitude hold, one-key take-off and two batteries.',
                        'price' => 39.90,
                        'stock' => 60,
                        'reviews' => [
                            ['Lukas P.', 3, 'Fun for beginners, but the video is shaky in the wind.'],
                            ['Marco R.', 4, 'Two batteries give about 25 minutes of flying. Good for the price.'],
                            ['Greta V.', 5, 'Easy to fly even for a first-timer, and it folds small enough for a jacket pocket.'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Beauty & Health',
                'description' => 'Personal care devices and wellness products.',
                'items' => [
                    [
                        'name' => 'Sonic Electric Toothbrush with 8 Heads',
                        'description' => 'Sonic toothbrush with five cleaning modes, a 2-minute timer, USB charging and 8 replacement heads.',
                        'price' => 17.99,
                        'stock' => 300,
                        'reviews' => [
                            ['Agnė L.', 5, 'My teeth feel as clean as after a dentist visit. The battery lasts a month.'],
                            ['Paweł Z.', 4, 'Strong vibration, but a bit loud in the morning.'],
                        ],
                    ],
                    [
                        'name' => 'Facial Cleansing Brush',
                        'description' => 'Waterproof silicone facial brush with three vibration levels for gentle daily cleansing.',
                        'price' => 12.80,
                        'stock' => 190,
                        'reviews' => [
                            ['Ieva G.', 4, 'Soft silicone that is gentle on my sensitive skin.'],
                            ['Olivia F.', 3, 'Does its job, but the charging cable is very short.'],
                        ],
                    ],
                    [
                        'name' => 'Ceramic Hair Straightener',
                        'description' => 'Ceramic plate straightener with adjustable temperature from 150 to 230 °C and a 30-second heat-up.',
                        'price' => 19.40,
                        'stock' => 110,
                        'reviews' => [
                            ['Marta N.', 5, 'Heats up very fast and leaves my hair smooth, not burnt.'],
                            ['Laura D.', 4, 'Works well, and the EU plug was included as promised.'],
                            ['Sofia L.', 2, 'The temperature display stopped working after a month.'],
                        ],
                    ],
                    [
                        'name' => 'Digital Body Weight Scale',
                        'description' => 'Tempered glass bathroom scale with a backlit display. Measures up to 180 kg in 0.1 kg steps.',
                        'price' => 13.25,
                        'stock' => 270,
                        'reviews' => [
                            ['Daniel H.', 5, 'Accurate, it matches the scale at my doctor\'s office.'],
                            ['Andrius T.', 4, 'Nice slim design and the batteries were included.'],
                            ['Karolis Š.', 4, 'Works fine, but the glass shows every footprint.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
