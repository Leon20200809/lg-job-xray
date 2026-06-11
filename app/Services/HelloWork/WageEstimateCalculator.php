<?php

namespace App\Services\HelloWork;

class WageEstimateCalculator
{
    private const TAKE_HOME_RATE = 0.78;

    /**
     * 正規化済みデータから賃金見積もりを計算する。
     *
     * @param array $data HelloWorkJobDataNormalizer が返した配列
     * @return array 計算結果
     */
    public function calculate(array $data): array
    {
        if (($data['wage_type'] ?? null) === '時給') {
            return $this->calculateHourlyJob($data);
        }

        if (($data['wage_type'] ?? null) === '月給') {
            return $this->calculateMonthlyJob($data);
        }

        return [
            'calculation_type' => '未対応',
            'message' => '賃金形態が未取得、または未対応のため計算できません。',
        ];
    }

    /**
     * 時給求人の計算。
     *
     * 時給求人は、求人票上の時給をそのまま基準にし、
     * 日給・月収・年収目安を出す。
     */
    private function calculateHourlyJob(array $data): array
    {
        $hourlyMin = $data['wage_min'] ?? null;
        $hourlyMax = $data['wage_max'] ?? null;
        $dailyHours = $data['scheduled_work_hours_per_day'] ?? null;

        // 月平均労働日数が取れない時給求人は、MVPでは月20日勤務で仮定する。
        $monthlyWorkDays = $data['monthly_work_days'] ?? 20;

        if ($hourlyMin === null || $hourlyMax === null || $dailyHours === null) {
            return [
                'calculation_type' => '時給求人',
                'message' => '時給または1日の実働時間が不足しているため、月収・年収を計算できません。',
            ];
        }

        $dailyPayMin = $hourlyMin * $dailyHours;
        $dailyPayMax = $hourlyMax * $dailyHours;

        $monthlyIncomeMin = $dailyPayMin * $monthlyWorkDays;
        $monthlyIncomeMax = $dailyPayMax * $monthlyWorkDays;

        $annualIncomeMin = $monthlyIncomeMin * 12;
        $annualIncomeMax = $monthlyIncomeMax * 12;

        return [
            'calculation_type' => '時給求人',

            'gross_hourly_wage_min' => round($hourlyMin),
            'gross_hourly_wage_max' => round($hourlyMax),

            'estimated_take_home_hourly_wage_min' => round($hourlyMin * self::TAKE_HOME_RATE),
            'estimated_take_home_hourly_wage_max' => round($hourlyMax * self::TAKE_HOME_RATE),

            'scheduled_work_hours_per_day' => $dailyHours,
            'assumed_monthly_work_days' => $monthlyWorkDays,

            'gross_daily_income_min' => round($dailyPayMin),
            'gross_daily_income_max' => round($dailyPayMax),

            'gross_monthly_income_min' => round($monthlyIncomeMin),
            'gross_monthly_income_max' => round($monthlyIncomeMax),

            'gross_annual_income_min' => round($annualIncomeMin),
            'gross_annual_income_max' => round($annualIncomeMax),

            'estimated_take_home_monthly_income_min' => round($monthlyIncomeMin * self::TAKE_HOME_RATE),
            'estimated_take_home_monthly_income_max' => round($monthlyIncomeMax * self::TAKE_HOME_RATE),

            'estimated_take_home_annual_income_min' => round($annualIncomeMin * self::TAKE_HOME_RATE),
            'estimated_take_home_annual_income_max' => round($annualIncomeMax * self::TAKE_HOME_RATE),

            'note' => '月平均労働日数が未取得の場合、月20日勤務として試算しています。',
        ];
    }

    /**
     * 月給求人の計算。
     *
     * 月給求人は、月給から年収・年間労働時間・時給換算を出す。
     */
    private function calculateMonthlyJob(array $data): array
    {
        $monthlySalaryMin = $data['wage_min'] ?? null;
        $monthlySalaryMax = $data['wage_max'] ?? null;
        $dailyHours = $data['scheduled_work_hours_per_day'] ?? null;
        $annualHolidays = $data['annual_holidays'] ?? null;
        $monthlyOvertimeHours = $data['monthly_overtime_hours'] ?? 0;

        if ($monthlySalaryMin === null || $monthlySalaryMax === null || $dailyHours === null || $annualHolidays === null) {
            return [
                'calculation_type' => '月給求人',
                'message' => '月給、1日の実働時間、年間休日数のいずれかが不足しているため、時給換算できません。',
            ];
        }

        $annualWorkDays = 365 - $annualHolidays;
        $annualScheduledHours = $annualWorkDays * $dailyHours;
        $annualHoursWithOvertime = $annualScheduledHours + ($monthlyOvertimeHours * 12);

        $grossAnnualIncomeMin = $monthlySalaryMin * 12;
        $grossAnnualIncomeMax = $monthlySalaryMax * 12;

        return [
            'calculation_type' => '月給求人',

            'gross_annual_income_min' => round($grossAnnualIncomeMin),
            'gross_annual_income_max' => round($grossAnnualIncomeMax),

            'annual_work_days' => $annualWorkDays,
            'annual_scheduled_work_hours' => round($annualScheduledHours, 2),
            'annual_work_hours_with_overtime' => round($annualHoursWithOvertime, 2),

            'gross_hourly_wage_min' => round($grossAnnualIncomeMin / $annualHoursWithOvertime),
            'gross_hourly_wage_max' => round($grossAnnualIncomeMax / $annualHoursWithOvertime),

            'estimated_take_home_annual_income_min' => round($grossAnnualIncomeMin * self::TAKE_HOME_RATE),
            'estimated_take_home_annual_income_max' => round($grossAnnualIncomeMax * self::TAKE_HOME_RATE),

            'estimated_take_home_hourly_wage_min' => round(($grossAnnualIncomeMin * self::TAKE_HOME_RATE) / $annualHoursWithOvertime),
            'estimated_take_home_hourly_wage_max' => round(($grossAnnualIncomeMax * self::TAKE_HOME_RATE) / $annualHoursWithOvertime),

            'note' => '賞与は未反映です。手取り額は概算として78%で試算しています。',
        ];
    }
}