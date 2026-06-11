<?php

namespace App\Services\HelloWork;

class EstimateResultFormatter
{
    /**
     * 計算結果を画面表示用データに変換する。
     *
     * @param array $estimate WageEstimateCalculator が返した計算結果
     * @param array $normalized HelloWorkJobDataNormalizer が返した正規化データ
     * @param int|null $minimumWage 最低賃金。MVPでは任意指定。
     * @param string|null $minimumWageArea 最低賃金の地域名。
     * @return array 画面表示用データ
     */
    public function format(
        array $estimate,
        array $normalized,
        ?int $minimumWage = null,
        ?string $minimumWageArea = null
    ): array {
        $calculationType = $estimate['calculation_type'] ?? '未対応';

        if ($calculationType === '時給求人') {
            return $this->formatHourlyJob($estimate, $normalized, $minimumWage, $minimumWageArea);
        }

        if ($calculationType === '月給求人') {
            return $this->formatMonthlyJob($estimate, $normalized, $minimumWage, $minimumWageArea);
        }

        return [
            'title' => '賃金見積もり',
            'summary' => $estimate['message'] ?? '計算できませんでした。',
            'items' => [],
            'alerts' => [],
            'notes' => [],
        ];
    }

    /**
     * 時給求人用の表示データを作る。
     */
    private function formatHourlyJob(
        array $estimate,
        array $normalized,
        ?int $minimumWage,
        ?string $minimumWageArea
    ): array {
        $items = [
            [
                'label' => '求人タイプ',
                'value' => '時給求人',
            ],
            [
                'label' => '額面時給',
                'value' => $this->yenRange(
                    $estimate['gross_hourly_wage_min'] ?? null,
                    $estimate['gross_hourly_wage_max'] ?? null
                ),
            ],
            [
                'label' => '概算手取り時給',
                'value' => $this->yenRange(
                    $estimate['estimated_take_home_hourly_wage_min'] ?? null,
                    $estimate['estimated_take_home_hourly_wage_max'] ?? null
                ),
            ],
            [
                'label' => '1日の実働時間',
                'value' => $this->hourValue($estimate['scheduled_work_hours_per_day'] ?? null),
            ],
            [
                'label' => '想定月間勤務日数',
                'value' => $this->dayValue($estimate['assumed_monthly_work_days'] ?? null),
            ],
            [
                'label' => '日給目安',
                'value' => $this->yenRange(
                    $estimate['gross_daily_income_min'] ?? null,
                    $estimate['gross_daily_income_max'] ?? null
                ),
            ],
            [
                'label' => '月収目安',
                'value' => $this->yenRange(
                    $estimate['gross_monthly_income_min'] ?? null,
                    $estimate['gross_monthly_income_max'] ?? null
                ),
            ],
            [
                'label' => '年収目安',
                'value' => $this->yenRange(
                    $estimate['gross_annual_income_min'] ?? null,
                    $estimate['gross_annual_income_max'] ?? null
                ),
            ],
            [
                'label' => '概算手取り月収',
                'value' => $this->yenRange(
                    $estimate['estimated_take_home_monthly_income_min'] ?? null,
                    $estimate['estimated_take_home_monthly_income_max'] ?? null
                ),
            ],
            [
                'label' => '概算手取り年収',
                'value' => $this->yenRange(
                    $estimate['estimated_take_home_annual_income_min'] ?? null,
                    $estimate['estimated_take_home_annual_income_max'] ?? null
                ),
            ],
        ];

        return [
            'title' => '時給求人の月収・年収目安',
            'summary' => sprintf(
                '額面時給は %s です。1日の実働時間と想定勤務日数から、月収・年収の目安を試算しています。',
                $this->yenRange(
                    $estimate['gross_hourly_wage_min'] ?? null,
                    $estimate['gross_hourly_wage_max'] ?? null
                )
            ),
            'items' => $items,
            'alerts' => $this->minimumWageAlerts($estimate, $minimumWage, $minimumWageArea),
            'notes' => $this->notes([
                $estimate['note'] ?? null,
                '概算手取り額は、額面金額の78%として簡易試算しています。',
                '実際の手取り額は、社会保険、税金、扶養、勤務条件により変動します。',
            ]),
        ];
    }

    /**
     * 月給求人用の表示データを作る。
     */
    private function formatMonthlyJob(
        array $estimate,
        array $normalized,
        ?int $minimumWage,
        ?string $minimumWageArea
    ): array {
        $items = [
            [
                'label' => '求人タイプ',
                'value' => '月給求人',
            ],
            [
                'label' => '額面年収',
                'value' => $this->yenRange(
                    $estimate['gross_annual_income_min'] ?? null,
                    $estimate['gross_annual_income_max'] ?? null
                ),
            ],
            [
                'label' => '額面時給換算',
                'value' => $this->yenRange(
                    $estimate['gross_hourly_wage_min'] ?? null,
                    $estimate['gross_hourly_wage_max'] ?? null
                ),
            ],
            [
                'label' => '概算手取り時給',
                'value' => $this->yenRange(
                    $estimate['estimated_take_home_hourly_wage_min'] ?? null,
                    $estimate['estimated_take_home_hourly_wage_max'] ?? null
                ),
            ],
            [
                'label' => '概算手取り年収',
                'value' => $this->yenRange(
                    $estimate['estimated_take_home_annual_income_min'] ?? null,
                    $estimate['estimated_take_home_annual_income_max'] ?? null
                ),
            ],
            [
                'label' => '年間労働日数',
                'value' => $this->dayValue($estimate['annual_work_days'] ?? null),
            ],
            [
                'label' => '年間所定労働時間',
                'value' => $this->hourValue($estimate['annual_scheduled_work_hours'] ?? null),
            ],
            [
                'label' => '残業込み年間労働時間',
                'value' => $this->hourValue($estimate['annual_work_hours_with_overtime'] ?? null),
            ],
        ];

        return [
            'title' => '月給求人の時給換算',
            'summary' => sprintf(
                '月給求人を年間労働時間で割り戻すと、額面時給換算は %s です。',
                $this->yenRange(
                    $estimate['gross_hourly_wage_min'] ?? null,
                    $estimate['gross_hourly_wage_max'] ?? null
                )
            ),
            'items' => $items,
            'alerts' => $this->minimumWageAlerts($estimate, $minimumWage, $minimumWageArea),
            'notes' => $this->notes([
                $estimate['note'] ?? null,
                '月給求人は、月給×12ヶ月を年間労働時間で割って時給換算しています。',
                '概算手取り額は、額面金額の78%として簡易試算しています。',
                '賞与、各種手当、税金、社会保険料、実際の残業時間は未反映の場合があります。',
            ]),
        ];
    }

    /**
     * 最低賃金との比較メッセージを作る。
     *
     * 注意：
     * 法定の最低賃金比較は、手取りではなく額面時給で見る。
     */
    private function minimumWageAlerts(array $estimate, ?int $minimumWage, ?string $area): array
    {
        if ($minimumWage === null) {
            return [];
        }

        $areaLabel = $area ? "{$area}最低賃金" : '最低賃金';

        $grossHourlyMin = $estimate['gross_hourly_wage_min'] ?? null;
        $takeHomeHourlyMin = $estimate['estimated_take_home_hourly_wage_min'] ?? null;

        $alerts = [];

        if ($grossHourlyMin !== null) {
            if ($grossHourlyMin < $minimumWage) {
                $alerts[] = [
                    'level' => 'warning',
                    'label' => '最低賃金比較（額面）',
                    'message' => "{$areaLabel} {$this->yen($minimumWage)} に対して、額面時給換算の下限 {$this->yen($grossHourlyMin)} は下回っています。",
                ];
            } else {
                $alerts[] = [
                    'level' => 'ok',
                    'label' => '最低賃金比較（額面）',
                    'message' => "{$areaLabel} {$this->yen($minimumWage)} に対して、額面時給換算の下限 {$this->yen($grossHourlyMin)} は上回っています。",
                ];
            }
        }

        if ($takeHomeHourlyMin !== null && $takeHomeHourlyMin < $minimumWage) {
            $alerts[] = [
                'level' => 'info',
                'label' => '生活感比較（概算手取り）',
                'message' => "概算手取り時給の下限 {$this->yen($takeHomeHourlyMin)} は、{$areaLabel} {$this->yen($minimumWage)} を下回ります。法定比較ではなく、生活感を見るための参考値です。",
            ];
        }

        return $alerts;
    }

    /**
     * 円レンジ表記。
     */
    private function yenRange(null|int|float $min, null|int|float $max): string
    {
        if ($min === null && $max === null) {
            return '未計算';
        }

        if ($min !== null && $max !== null && (float) $min === (float) $max) {
            return $this->yen($min);
        }

        return $this->yen($min) . '〜' . $this->yen($max);
    }

    /**
     * 円表記。
     */
    private function yen(null|int|float $value): string
    {
        if ($value === null) {
            return '未計算';
        }

        return number_format((float) $value) . '円';
    }

    /**
     * 時間表記。
     */
    private function hourValue(null|int|float $value): string
    {
        if ($value === null) {
            return '未計算';
        }

        return rtrim(rtrim((string) round((float) $value, 2), '0'), '.') . '時間';
    }

    /**
     * 日数表記。
     */
    private function dayValue(null|int|float $value): string
    {
        if ($value === null) {
            return '未計算';
        }

        return rtrim(rtrim((string) round((float) $value, 2), '0'), '.') . '日';
    }

    /**
     * 空の注記を除外する。
     */
    private function notes(array $notes): array
    {
        return array_values(array_filter($notes, function (?string $note): bool {
            return $note !== null && $note !== '';
        }));
    }
}