<?php

namespace App\Services\HelloWork;

class HelloWorkJobDataNormalizer
{
    /**
     * Parserが抽出した求人データ文字列を、計算しやすい数値データに変換する。
     *
     * @param array $parsed HelloWorkHtmlParser が返した配列
     * @return array 計算用に正規化したデータ
     */
    public function normalize(array $parsed): array
    {
        $wageType = $parsed['wage_type'] ?? null;
        $wageRange = $this->moneyRange($parsed['wage_range'] ?? null);
        $baseSalaryRange = $this->moneyRange($parsed['base_salary_range'] ?? null);
        $fixedOvertimeAmountRange = $this->moneyRange($parsed['fixed_overtime_amount'] ?? null);

        $breakMinutes = $this->minutes($parsed['break_time'] ?? null);
        $workingTime = $this->workingTime($parsed['working_time_1'] ?? null, $breakMinutes);

        return [
            // 基本情報
            'job_number' => $parsed['job_number'] ?? null,
            'company_name' => $parsed['company_name'] ?? null,
            'job_title' => $parsed['job_title'] ?? null,

            // 賃金形態
            'wage_type' => $wageType,

            // 賃金レンジ
            // 月給求人なら月給、時給求人なら時給として扱う。
            'wage_min' => $wageRange['min'],
            'wage_max' => $wageRange['max'],

            // 基本給レンジ
            'base_salary_min' => $baseSalaryRange['min'],
            'base_salary_max' => $baseSalaryRange['max'],

            // 固定残業代
            'fixed_overtime_status' => $parsed['fixed_overtime_status'] ?? null,
            'fixed_overtime_amount_min' => $fixedOvertimeAmountRange['min'],
            'fixed_overtime_amount_max' => $fixedOvertimeAmountRange['max'],
            'fixed_overtime_hours' => $this->fixedOvertimeHours(
                $parsed['fixed_overtime_status'] ?? null,
                $parsed['fixed_overtime_note'] ?? null
            ),

            // 労働時間
            'working_time_1' => $parsed['working_time_1'] ?? null,
            'work_start_time' => $workingTime['start'],
            'work_end_time' => $workingTime['end'],
            'scheduled_work_minutes_per_day' => $workingTime['work_minutes'],
            'scheduled_work_hours_per_day' => $workingTime['work_hours'],

            // 休憩
            'break_minutes' => $breakMinutes,

            // 残業
            'monthly_overtime_hours' => $this->hours($parsed['monthly_overtime'] ?? null),

            // 休日・労働日数
            'annual_holidays' => $this->days($parsed['annual_holidays'] ?? null),
            'monthly_work_days' => $this->floatNumber($parsed['monthly_work_days'] ?? null),

            // 賞与
            'bonus_status' => $parsed['bonus_status'] ?? null,
            'bonus_previous_result' => $parsed['bonus_previous_result'] ?? null,

            // 休日文字列
            'holiday' => $parsed['holiday'] ?? null,
            'weekly_two_days' => $parsed['weekly_two_days'] ?? null,
        ];
    }

    /**
     * 金額レンジを数値化する。
     *
     * 例：
     * 212,700円〜212,700円
     * → ['min' => 212700, 'max' => 212700]
     *
     * 1,200円〜1,250円
     * → ['min' => 1200, 'max' => 1250]
     */
    private function moneyRange(?string $text): array
    {
        $text = $this->toHalfWidth($text);

        if ($text === null || $text === '' || $text === '-' || $text === 'なし') {
            return [
                'min' => null,
                'max' => null,
            ];
        }

        preg_match_all('/\d+(?:,\d{3})*/u', $text, $matches);

        $numbers = array_map(function (string $number): int {
            return (int) str_replace(',', '', $number);
        }, $matches[0] ?? []);

        return [
            'min' => $numbers[0] ?? null,
            'max' => $numbers[1] ?? ($numbers[0] ?? null),
        ];
    }

    /**
     * 「19時間」「10時間」などから時間数を取り出す。
     */
    private function hours(?string $text): ?float
    {
        $text = $this->toHalfWidth($text);

        if ($text === null || $text === '' || $text === '-' || $text === 'なし') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*時間/u', $text, $match)) {
            return (float) $match[1];
        }

        return null;
    }

    /**
     * 「60分」などから分数を取り出す。
     */
    private function minutes(?string $text): ?int
    {
        $text = $this->toHalfWidth($text);

        if ($text === null || $text === '' || $text === '-' || $text === 'なし') {
            return null;
        }

        if (preg_match('/(\d+)\s*分/u', $text, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    /**
     * 「116日」などから日数を取り出す。
     */
    private function days(?string $text): ?int
    {
        $text = $this->toHalfWidth($text);

        if ($text === null || $text === '' || $text === '-' || $text === 'なし') {
            return null;
        }

        if (preg_match('/(\d+)\s*日/u', $text, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    /**
     * 「20.7日」などから小数を取り出す。
     */
    private function floatNumber(?string $text): ?float
    {
        $text = $this->toHalfWidth($text);

        if ($text === null || $text === '' || $text === '-' || $text === 'なし') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)/u', $text, $match)) {
            return (float) $match[1];
        }

        return null;
    }

    /**
     * 固定残業代に関する特記事項から、固定残業時間を取り出す。
     *
     * 例：
     * １９時間まで残業の有無に関わらず支給いたします。
     * → 19
     */
    private function fixedOvertimeHours(?string $status, ?string $note): ?float
    {
        if ($status !== 'あり') {
            return null;
        }

        return $this->hours($note);
    }

    /**
     * 就業時間から開始・終了・実働時間を算出する。
     *
     * 例：
     * 10時00分〜15時00分
     * 休憩60分
     * → 実働4時間
     */
    private function workingTime(?string $text, ?int $breakMinutes): array
    {
        $text = $this->toHalfWidth($text);

        $empty = [
            'start' => null,
            'end' => null,
            'work_minutes' => null,
            'work_hours' => null,
        ];

        if ($text === null || $text === '') {
            return $empty;
        }

        if (! preg_match('/(\d{1,2})時(\d{1,2})分\s*[〜~\-－]\s*(\d{1,2})時(\d{1,2})分/u', $text, $match)) {
            return $empty;
        }

        $startHour = (int) $match[1];
        $startMinute = (int) $match[2];
        $endHour = (int) $match[3];
        $endMinute = (int) $match[4];

        $startTotalMinutes = $startHour * 60 + $startMinute;
        $endTotalMinutes = $endHour * 60 + $endMinute;

        // 念のため、日付またぎにも対応する。
        if ($endTotalMinutes < $startTotalMinutes) {
            $endTotalMinutes += 24 * 60;
        }

        $grossMinutes = $endTotalMinutes - $startTotalMinutes;
        $actualBreakMinutes = $breakMinutes ?? 0;
        $workMinutes = max(0, $grossMinutes - $actualBreakMinutes);

        return [
            'start' => sprintf('%02d:%02d', $startHour, $startMinute),
            'end' => sprintf('%02d:%02d', $endHour, $endMinute),
            'work_minutes' => $workMinutes,
            'work_hours' => round($workMinutes / 60, 2),
        ];
    }

    /**
     * 全角数字・全角記号を半角へ寄せる。
     *
     * 例：
     * １９時間 → 19時間
     * １，２００円 → 1,200円
     */
    private function toHalfWidth(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return trim(mb_convert_kana($text, 'as', 'UTF-8'));
    }
}