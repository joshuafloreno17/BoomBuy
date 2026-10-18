<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * Opens every GET page as every role, then follows every link on those
 * pages (tabs, filters, detail pages), and looks for broken pages: server
 * errors, text before <!DOCTYPE, Blade/PHP code shown as text.
 * Following links matters: Admin → Logistics only broke on ?tab=branches.
 */
class PageSweepTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    public function test_every_page_opens_cleanly_for_every_role(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller);
        $this->addVariation($product);
        $buyer = $this->makeUser();
        $rider = $this->makeRider();
        $logistics = $this->makeLogistics();
        $order = $this->makeOrder($buyer, $product, 'Delivered', ['delivery_rider_id' => $rider->id]);
        $this->actingAsAdmin(); // the admin row exists before any page asks for "BoomBuy Support"
        $this->flushSession();

        $values = [
            'id' => $order, 'orderId' => $order, 'order' => $order,
            'productId' => $product->id, 'product' => $product->id,
            'userId' => $seller->id, 'user' => $seller->id, 'sellerId' => $seller->id,
            'riderId' => $rider->id, 'rider' => $rider->id,
            'category' => 'shoes', 'slug' => 'shoes', 'field' => 'drivers_license',
            'token' => 'x', 'code' => 'x', 'path' => 'x', 'areaId' => 1, 'imageId' => 1,
        ];

        $roles = [
            'guest' => null,
            'buyer' => $buyer,
            'seller' => $seller,
            'rider' => $rider,
            'logistics' => $logistics,
            'admin' => 'admin',
        ];

        $problems = [];
        $checked = 0;
        $seeds = [];

        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods(), true) || str_starts_with($route->uri(), 'api/') || str_starts_with($route->uri(), '_')) {
                continue;
            }

            $params = [];
            foreach ($route->parameterNames() as $name) {
                $params[$name] = $values[$name] ?? 1;
            }

            $seeds[] = url(preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $params[$m[1]] ?? '', $route->uri()));
        }

        $base = rtrim(url('/'), '/');

        foreach ($roles as $role => $user) {
            // Every route, then every link found on the pages (tabs, filters,
            // detail pages), like someone clicking through the whole site.
            $queue = $seeds;
            $seen = [];

            while ($queue && count($seen) < 900) {
                $url = array_shift($queue);
                $url = strtok($url, '#');

                if (isset($seen[$url]) || preg_match('#/(logout|download|downloads|storage|evidence|document)\b#', $url) || preg_match('#\.(apk|pdf|png|jpe?g|webp|svg|css|js|json)(\?|$)#i', $url)) {
                    continue;
                }

                $seen[$url] = true;
                $this->flushSession();

                if ($user === 'admin') {
                    $this->actingAsAdmin();
                } elseif ($user) {
                    $this->actingAsUser($user);
                }

                $response = $this->get($url);
                $checked++;
                $status = $response->status();
                $where = "{$role} GET " . substr($url, strlen($base));

                if ($status >= 500) {
                    $problems[] = "{$where} → {$status} " . substr(strip_tags((string) ($response->exception?->getMessage() ?? '')), 0, 160);
                    continue;
                }

                $type = (string) $response->headers->get('Content-Type');
                if ($status !== 200 || !str_contains($type, 'text/html')) {
                    continue;
                }

                $html = (string) $response->getContent();
                $start = ltrim($html);

                if ($start !== '' && $start[0] !== '<') {
                    $problems[] = "{$where} → text before the page: " . substr($start, 0, 80);
                }

                // Blade or PHP that leaked out as text.
                $visible = preg_replace('#<(script|style)\b.*?</\1>#si', '', $html);
                foreach (['{{ $', '{!! $', '@endif', '@endforeach', '@include(', '@php', 'Undefined variable', 'Undefined array key', 'ErrorException', 'Call to undefined', 'htmlspecialchars()'] as $needle) {
                    if (str_contains($visible, $needle)) {
                        $problems[] = "{$where} → shows '{$needle}'";
                    }
                }

                preg_match_all('#href="([^"]+)"#', $html, $links);
                foreach ($links[1] as $href) {
                    $href = html_entity_decode($href);
                    if (str_starts_with($href, '/')) {
                        $href = $base . $href;
                    }
                    if (str_starts_with($href, $base) && !isset($seen[strtok($href, '#')])) {
                        $queue[] = $href;
                    }
                }
            }

            fwrite(STDERR, "\n{$role}: " . count($seen) . ' pages');
        }

        fwrite(STDERR, "\nChecked {$checked} page loads.\n");
        $this->assertSame([], $problems, implode("\n", $problems));
    }
}
