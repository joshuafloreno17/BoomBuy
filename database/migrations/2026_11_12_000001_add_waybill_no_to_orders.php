<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A random waybill no. per order ("BB" + 10 digits) instead of one made
     * from the order id. Every order so far gets one, and labels already
     * printed are updated to show it.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('waybill_no', 16)->nullable()->unique();
        });

        $used = [];

        DB::table('orders')->orderBy('id')->select('id', 'waybill_snapshot')->chunkById(500, function ($orders) use (&$used) {
            foreach ($orders as $order) {
                do {
                    $number = 'BB' . random_int(1, 9) . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
                } while (isset($used[$number]));

                $used[$number] = true;
                $update = ['waybill_no' => $number];

                $label = $order->waybill_snapshot ? json_decode($order->waybill_snapshot, true) : null;

                if (is_array($label)) {
                    $label['waybill'] = $number;
                    $label['scan_url'] = preg_replace('#/logistics/scan/[^/?\#]+#', '/logistics/scan/' . $number, (string) ($label['scan_url'] ?? ''));
                    $update['waybill_snapshot'] = json_encode($label);
                }

                DB::table('orders')->where('id', $order->id)->update($update);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['waybill_no']);
            $table->dropColumn('waybill_no');
        });
    }
};
