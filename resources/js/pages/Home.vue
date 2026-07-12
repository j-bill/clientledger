<template>
  <div>
    <EmailVerificationDialog
      v-model="showEmailVerificationDialog"
      @verified="onEmailVerified"
      @skipped="onVerificationSkipped"
    />

    <div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
      <!-- Hero Section - This Month Focus -->
      <ui-card>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="flex flex-col justify-center">
            <h1 class="text-2xl font-semibold tracking-tight">{{ $t('pages.home.yourMoneyToday') }}</h1>
            <p class="mt-1 text-sm text-bone-500">{{ $t('pages.home.trackGrowth', { type: isAdmin ? 'revenue' : 'earnings' }) }}</p>
            <div class="mt-6">
              <div class="font-mono text-[11px] uppercase tracking-wider text-bone-500">{{ isAdmin ? $t('pages.home.thisMonthRevenue') : $t('pages.home.thisMonthEarnings') }}</div>
              <div class="tnum mt-1 text-4xl font-semibold text-brass-400">{{ formatCurrency(isAdmin ? kpis.revenue.monthly.actual : kpis.earnings.monthly.actual) }}</div>
              <div class="mt-2 flex items-center gap-1.5 text-xs text-ochre-400" v-if="(isAdmin ? kpis.revenue.is_extrapolated : kpis.earnings.is_extrapolated)">
                <Zap class="h-3.5 w-3.5 shrink-0" />
                <span>{{ $t('pages.home.extrapolatedEstimate') }}: <span class="tnum">{{ formatCurrency(isAdmin ? kpis.revenue.monthly.extrapolated : kpis.earnings.monthly.extrapolated) }}</span></span>
              </div>
            </div>
          </div>
          <div class="flex items-center justify-center">
            <div class="hero-chart h-[200px] w-full overflow-hidden rounded-md">
              <GChart
                type="LineChart"
                :data="heroTrendData"
                :options="heroTrendChartOptions"
              />
            </div>
          </div>
        </div>
      </ui-card>

      <!-- Key Metrics Row -->
      <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- This Year -->
        <div class="rounded-lg border border-ink-700/60 bg-ink-900 p-4">
          <div class="flex items-center gap-2">
            <Calendar class="h-3.5 w-3.5 text-bone-500" />
            <span class="font-mono text-[11px] uppercase tracking-wider text-bone-500">{{ $t('pages.home.thisYear') }}</span>
          </div>
          <div class="tnum mt-2 text-2xl font-semibold text-bone-100">{{
            formatCurrency(isAdmin ? kpis.revenue.yearly.actual : kpis.earnings.yearly.actual)
          }}</div>
          <div class="mt-3 space-y-1">
            <div class="flex justify-between text-xs" v-if="(isAdmin ? kpis.revenue.yearly.extrapolated : kpis.earnings.yearly.extrapolated)">
              <span class="text-bone-500">{{ $t('pages.home.estimated') }}:</span>
              <span class="tnum text-bone-300">{{ formatCurrency(isAdmin ? kpis.revenue.yearly.extrapolated : kpis.earnings.yearly.extrapolated) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.hours') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.yearly.actual) }}</span>
            </div>
            <div class="flex justify-between text-xs" v-if="kpis.hours.yearly.extrapolated && kpis.hours.yearly.extrapolated != kpis.hours.yearly.actual">
              <span class="text-bone-500">{{ $t('pages.home.estimated') }} {{ $t('pages.home.hours') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.yearly.extrapolated) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.billable') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.yearly.actual_billable) }}</span>
            </div>
            <div class="flex justify-between text-xs" v-if="kpis.hours.yearly.extrapolated_billable && kpis.hours.yearly.extrapolated_billable != kpis.hours.yearly.actual_billable">
              <span class="text-bone-500">{{ $t('pages.home.estimated') }} {{ $t('pages.home.billable') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.yearly.extrapolated_billable) }}</span>
            </div>
          </div>
        </div>

        <!-- This Month - Featured -->
        <div class="rounded-lg border border-ink-700/60 bg-ink-900 p-4">
          <div class="flex items-center gap-2">
            <Zap class="h-3.5 w-3.5 text-bone-500" />
            <span class="font-mono text-[11px] uppercase tracking-wider text-bone-500">{{ $t('pages.home.thisMonth') }}</span>
          </div>
          <div class="tnum mt-2 text-2xl font-semibold text-bone-100">{{
            formatCurrency(isAdmin ? kpis.revenue.monthly.actual : kpis.earnings.monthly.actual)
          }}</div>
          <div class="mt-3 space-y-1">
            <div class="flex justify-between text-xs" v-if="(isAdmin ? kpis.revenue.is_extrapolated : kpis.earnings.is_extrapolated)">
              <span class="text-bone-500">{{ $t('pages.home.estimated') }}:</span>
              <span class="tnum text-bone-300">{{ formatCurrency(isAdmin ? kpis.revenue.monthly.extrapolated : kpis.earnings.monthly.extrapolated) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.totalHours') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.monthly.actual) }}</span>
            </div>
            <div class="flex justify-between text-xs" v-if="kpis.hours.monthly.extrapolated && kpis.hours.monthly.extrapolated != kpis.hours.monthly.actual">
              <span class="text-bone-500">{{ $t('pages.home.estimated') }} {{ $t('pages.home.hours') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.monthly.extrapolated) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.billable') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.monthly.actual_billable) }}</span>
            </div>
            <div class="flex justify-between text-xs" v-if="kpis.hours.monthly.extrapolated_billable && kpis.hours.monthly.extrapolated_billable != kpis.hours.monthly.actual_billable">
              <span class="text-bone-500">{{ $t('pages.home.estimated') }} {{ $t('pages.home.billable') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.monthly.extrapolated_billable) }}</span>
            </div>
          </div>
        </div>

        <!-- Last Month -->
        <div class="rounded-lg border border-ink-700/60 bg-ink-900 p-4">
          <div class="flex items-center gap-2">
            <History class="h-3.5 w-3.5 text-bone-500" />
            <span class="font-mono text-[11px] uppercase tracking-wider text-bone-500">{{ $t('pages.home.lastMonth') }}</span>
          </div>
          <div class="tnum mt-2 text-2xl font-semibold text-bone-100">{{
            formatCurrency(isAdmin ? kpis.revenue.last_month.paid : kpis.earnings.last_month.paid)
          }}</div>
          <div class="mt-3 space-y-1">
            <div class="flex justify-between text-xs" v-if="(isAdmin ? kpis.revenue.last_month.due : kpis.earnings.last_month.due) > 0">
              <span class="text-bone-500">{{ $t('common.due') }}:</span>
              <span class="tnum text-ochre-400">{{ formatCurrency(isAdmin ? kpis.revenue.last_month.due : kpis.earnings.last_month.due) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.hours') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.last_month.total) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.billable') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.last_month.billable) }}</span>
            </div>
          </div>
        </div>

        <!-- Last Year -->
        <div class="rounded-lg border border-ink-700/60 bg-ink-900 p-4">
          <div class="flex items-center gap-2">
            <TrendingUp class="h-3.5 w-3.5 text-bone-500" />
            <span class="font-mono text-[11px] uppercase tracking-wider text-bone-500">{{ $t('pages.home.lastYear') }}</span>
          </div>
          <div class="tnum mt-2 text-2xl font-semibold text-bone-100">{{
            formatCurrency(isAdmin ? kpis.revenue.last_year.paid : kpis.earnings.last_year.paid)
          }}</div>
          <div class="mt-3 space-y-1">
            <div class="flex justify-between text-xs" v-if="(isAdmin ? kpis.revenue.last_year.due : kpis.earnings.last_year.due) > 0">
              <span class="text-bone-500">{{ $t('common.due') }}:</span>
              <span class="tnum text-ochre-400">{{ formatCurrency(isAdmin ? kpis.revenue.last_year.due : kpis.earnings.last_year.due) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.hours') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.last_year.total) }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-bone-500">{{ $t('pages.home.billable') }}:</span>
              <span class="tnum text-bone-300">{{ formatHours(kpis.hours.last_year.billable) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row 1: Trends -->
      <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <!-- Revenue/Earnings Trend Chart -->
        <ui-card class="md:col-span-2">
          <template #title>
            <span class="flex items-center gap-2">
              <ChartLine class="h-4 w-4 text-bone-500" />
              {{ isAdmin ? $t('pages.home.revenue') : $t('pages.home.earnings') }} {{ $t('pages.home.trend') }} ({{ $t('pages.home.lastTwelveMonths') }})
            </span>
          </template>
          <GChart
            type="LineChart"
            :data="isAdmin ? revenueChartData : earningsChartData"
            :options="isAdmin ? dynamicRevenueChartOptions : dynamicEarningsChartOptions"
          />
        </ui-card>

        <!-- Top Customers or Top Projects Chart -->
        <ui-card>
          <template #title>
            <span class="flex items-center gap-2">
              <Crown class="h-4 w-4 text-bone-500" />
              {{ isAdmin ? $t('pages.home.topCustomers') : $t('pages.home.topProjects') }}
            </span>
          </template>
          <GChart
            type="BarChart"
            :data="isAdmin ? customerChartData : projectEarningsChartData"
            :options="customerChartOptions"
          />
        </ui-card>
      </div>

      <!-- Charts Row 2: Hours & Deadlines -->
      <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <!-- Hours Worked Chart -->
        <ui-card class="md:col-span-2">
          <template #title>
            <span class="flex items-center gap-2">
              <Clock class="h-4 w-4 text-bone-500" />
              {{ $t('pages.home.hoursWorkedThisMonth') }}
            </span>
          </template>
          <GChart
            type="ColumnChart"
            :data="hoursChartData"
            :options="hoursChartOptions"
          />
        </ui-card>

        <!-- Upcoming Deadlines -->
        <ui-card>
          <template #title>
            <span class="flex items-center gap-2">
              <CalendarClock class="h-4 w-4 text-bone-500" />
              {{ $t('pages.home.upcomingDeadlines') }}
            </span>
          </template>
          <div v-if="upcomingDeadlines.length > 0" class="space-y-1">
            <div
              v-for="deadline in upcomingDeadlines.slice(0, 5)"
              :key="deadline.id"
              class="flex items-center gap-3 rounded-md px-2 py-2.5 hover:bg-ink-850"
            >
              <component
                :is="deadline.days_until <= 3 ? CircleAlert : ClockIcon"
                class="h-4 w-4 shrink-0"
                :class="deadline.days_until <= 3 ? 'text-clay-400' : (deadline.days_until <= 7 ? 'text-ochre-400' : 'text-sage-400')"
              />
              <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium text-bone-100">{{ deadline.name }}</div>
                <div class="truncate text-xs text-bone-500">{{ deadline.customer }} • <span class="tnum">{{ formatDate(deadline.deadline) }}</span></div>
              </div>
              <ui-chip :color="deadline.days_until <= 3 ? 'error' : (deadline.days_until <= 7 ? 'warning' : 'success')">
                <span class="tnum">{{ deadline.days_until }}{{ $t('pages.home.daysSuffix') }}</span>
              </ui-chip>
            </div>
          </div>
          <div v-else class="py-10 text-center">
            <CheckCheck class="mx-auto mb-2 h-10 w-10 text-bone-700" />
            <div class="text-sm text-bone-500">{{ $t('pages.home.noUpcomingDeadlines') }}</div>
          </div>
        </ui-card>
      </div>
    </div>
  </div>
</template>

<script>
import { GChart } from "vue-google-charts";
import axios from "axios";
import { mapState, mapActions } from 'pinia';
import { store } from '../store';
import EmailVerificationDialog from '../components/EmailVerificationDialog.vue';
import { formatCurrency, formatDate } from '../utils/formatters';
import { Zap, Calendar, History, TrendingUp, ChartLine, Crown, Clock, CalendarClock, CircleAlert, CheckCheck } from 'lucide-vue-next';

export default {
  name: "Home",
  components: {
    GChart,
    EmailVerificationDialog,
    Zap,
    Calendar,
    History,
    TrendingUp,
    ChartLine,
    Crown,
    Clock,
    CalendarClock,
    CheckCheck,
  },
  setup() {
    // Icons used via dynamic :is bindings
    return { CircleAlert, ClockIcon: Clock };
  },
  data() {
    return {
      showEmailVerificationDialog: false,
      verificationCheckTimeout: null,
      kpis: {
        revenue: {
          yearly: 0,
          last_year: 0,
          monthly: 0,
          last_month: 0,
          is_extrapolated: false,
        },
        hours: {
          yearly: { total: 0, billable: 0 },
          last_year: { total: 0, billable: 0 },
          monthly: { total: 0, billable: 0 },
          last_month: { total: 0, billable: 0 },
        },
        projects: {
          active: 0,
          overdue: 0,
        },
        earnings: {
          yearly: 0,
          last_year: 0,
          monthly: 0,
          last_month: 0,
          is_extrapolated: false,
        }
      },
      revenueByCustomer: {},
      yearlyRevenueTrend: [],
      heroTrendDataRaw: [],
      earningsByProject: {},
      yearlyEarningsTrend: [],
      monthlyHours: [],
      upcomingDeadlines: [],
      heroTrendChartOptions: {
        curveType: "function",
        legend: "none",
        height: 200,
        backgroundColor: "transparent",
        chartArea: {
          backgroundColor: "transparent",
          left: "0%",
          right: "0%",
          top: "5%",
          bottom: "5%",
        },
        pointSize: 5,
        lineWidth: 2,
        vAxis: {
          textStyle: { color: "transparent" },
          gridlines: { color: "transparent" },
          baselineColor: "transparent",
        },
        hAxis: {
          textStyle: { color: "transparent" },
          gridlines: { color: "transparent" },
          baselineColor: "transparent",
        },
        series: {
          0: {
            color: "#c9a94f",
            lineWidth: 3,
            pointSize: 5,
            lineDashStyle: [1, 0], // Solid line for actual
          },
          1: {
            color: "#d2a458",
            lineWidth: 2,
            pointSize: 0,
            lineDashStyle: [5, 5], // Dashed line for projection
          },
        },
        annotations: {
          alwaysOutside: true,
          textStyle: {
            fontSize: 10,
            color: '#d2a458',
            bold: true,
          },
        },
        animation: {
          duration: 1200,
          easing: 'inAndOut',
          startup: true,
        },
      },
      revenueChartOptions: {
        curveType: "line",
        legend: { position: "top", textStyle: { color: "#a3a093", fontSize: 13 } },
        height: 350,
        backgroundColor: "transparent",
        chartArea: {
          backgroundColor: "transparent",
          left: "5%",
          right: "5%",
          top: "15%",
          bottom: "10%",
        },
        pointSize: 8,
        pointShape: "circle",
        vAxis: {
          format: "$#,###",
          textStyle: { color: "#a3a093", fontSize: 11 },
          gridlines: {
            color: "#34342c",
            interval: 1,
          },
        },
        hAxis: {
          textStyle: { color: "#a3a093", fontSize: 11 },
          gridlines: {
            color: "#34342c",
            interval: 1,
          },
          showTextEvery: 7,
          format: "MMM",
        },
        series: {
          0: {
            color: "#c9a94f",
            lineWidth: 3,
            pointSize: 6,
            areaOpacity: 0.2,
          },
        },
        animation: {
          duration: 800,
          easing: 'inAndOut',
          startup: true,
        },
      },
      customerChartOptions: {
        legend: {
          position: "none",
        },
        height: 350,
        backgroundColor: "transparent",
        chartArea: {
          backgroundColor: "transparent",
          left: "25%",
          right: "5%",
          top: "5%",
          bottom: "5%",
        },
        hAxis: {
          textStyle: { color: "#a3a093", fontSize: 12 },
          gridlines: { color: "#34342c" },
          baselineColor: "transparent",
        },
        vAxis: {
          textStyle: { color: "#a3a093", fontSize: 12 },
          gridlines: { color: "transparent" },
          baselineColor: "transparent",
        },
        colors: ["#c9a94f"],
        animation: {
          duration: 1000,
          easing: 'inAndOut',
          startup: true,
        },
      },
      hoursChartOptions: {
        legend: {
          position: "top",
          textStyle: { color: "#a3a093", fontSize: 13 },
        },
        height: 350,
        backgroundColor: "transparent",
        chartArea: {
          backgroundColor: "transparent",
          left: "5%",
          right: "5%",
          top: "15%",
          bottom: "10%",
        },
        isStacked: false,
        bar: { groupWidth: "70%" },
        pointSize: 5,
        vAxis: {
          format: "#.#h",
          textStyle: { color: "#a3a093", fontSize: 11 },
          gridlines: { color: "#34342c" },
        },
        hAxis: {
          textStyle: { color: "#a3a093", fontSize: 11 },
          gridlines: { color: "transparent" },
          showTextEvery: 2,
        },
        series: {
          0: {
            color: "#6f9a7e",
            targetAxisIndex: 0,
          },
          1: {
            color: "#93a8bd",
            targetAxisIndex: 0,
          },
        },
        animation: {
          duration: 600,
          easing: 'inAndOut',
          startup: true,
        },
      },
    };
  },
  computed: {
    ...mapState(store, ['user', 'settings']),
    isAdmin() {
      return this.user?.role === 'admin';
    },
    currencyFormat() {
      // Google Charts uses ICU number format patterns
      // Instead of embedding the symbol, we'll rely on basic patterns
      // and let the chart apply currency symbol via formatter
      const numberFormat = this.settings?.number_format || 'en-US';

      switch (numberFormat) {
        case 'de-DE': // 1.234,56
          return '#.##0,00';
        case 'fr-FR': // 1 234,56
          return '#,##0.00'; // Google Charts doesn't support space as thousands sep in patterns
        case 'en-IN': // 12,34,567.89
          return '#,##0.00';
        case 'en-US': // 1,234.56
        default:
          return '#,##0.00';
      }
    },
    numberFormatString() {
      // Generate number format string (without currency) based on number_format setting
      const numberFormat = this.settings?.number_format || 'en-US';

      switch (numberFormat) {
        case 'de-DE': // 1.234,56
          return '#.##0,00';
        case 'fr-FR': // 1 234,56
          return '#,##0.00';
        case 'en-IN': // 12,34,567.89
          return '#,##0.00';
        case 'en-US': // 1,234.56
        default:
          return '#,##0.00';
      }
    },
    dynamicRevenueChartOptions() {
      // Create a copy and update with dynamic format based on number_format setting
      const options = JSON.parse(JSON.stringify(this.revenueChartOptions));

      // Note: Google Charts format property has limited support for custom decimal separators
      // The format '#,##0.00' works for most locales, but European formats with comma
      // as decimal separator need to be handled via custom formatters if needed
      // For now, keep the standard format that works universally
      options.vAxis.format = '#,##0.00';

      return options;
    },
    dynamicEarningsChartOptions() {
      // Same as revenue for now
      return this.dynamicRevenueChartOptions;
    },
    revenueChartData() {
      const headers = [this.$t('pages.home.chartLabels.date'), this.$t('pages.home.chartLabels.revenue')];
      const data = this.yearlyRevenueTrend.map((item) => [
        new Date(item.date),
        parseFloat(item.amount),
      ]);
      return [headers, ...data];
    },
    earningsChartData() {
      const headers = [this.$t('pages.home.chartLabels.date'), this.$t('pages.home.chartLabels.earnings')];
      const data = this.yearlyEarningsTrend?.map((item) => [
        new Date(item.date),
        parseFloat(item.amount),
      ]) || [];
      return [headers, ...data];
    },
    customerChartData() {
      const headers = [this.$t('pages.home.chartLabels.customer'), this.$t('pages.home.chartLabels.revenue')];
      const data = Object.entries(this.revenueByCustomer || {})
        .sort((a, b) => parseFloat(b[1]) - parseFloat(a[1])) // Sort by revenue descending
        .slice(0, 5) // Take only top 5
        .map(([customer, amount]) => [customer, parseFloat(amount)]);
      return [headers, ...data];
    },
    projectEarningsChartData() {
      const headers = [this.$t('pages.home.chartLabels.project'), this.$t('pages.home.chartLabels.earnings')];
      const data = Object.entries(this.earningsByProject || {}).map(
        ([project, amount]) => [project, parseFloat(amount)]
      );
      return [headers, ...data];
    },
    hoursChartData() {
      const headers = [this.$t('pages.home.chartLabels.date'), this.$t('pages.home.chartLabels.billable'), this.$t('pages.home.chartLabels.nonBillable')];
      const data = this.monthlyHours.map((item) => {
        const total = parseFloat(item.hours);
        const billable = parseFloat(item.billable || 0);
        const nonBillable = total - billable;
        return [new Date(item.date), billable, nonBillable];
      });
      return [headers, ...data];
    },
    heroTrendData() {
      const trend = this.heroTrendDataRaw || [];

      if (!trend || trend.length === 0) {
        return [[this.$t('pages.home.chartLabels.month'), this.$t('pages.home.chartLabels.actual'), this.$t('pages.home.chartLabels.projection'), { role: 'annotation' }]];
      }

      // Get the last 12 months of actual data
      const actualData = trend.slice(-12).map((item) => [
        item.date,
        parseFloat(item.amount),
      ]);

      // Calculate trend line for projection
      if (actualData.length < 2) {
        return [
          [this.$t('pages.home.chartLabels.month'), this.$t('pages.home.chartLabels.actual'), this.$t('pages.home.chartLabels.projection'), { role: 'annotation' }],
          ...actualData.map((item, idx) => [
            new Date(item[0]).toLocaleDateString('en-US', { year: '2-digit', month: 'short' }),
            item[1],
            null,
            null,
          ]),
        ];
      }

      // Simple linear regression to predict next 4 months
      const n = actualData.length;
      const xValues = Array.from({ length: n }, (_, i) => i);
      const yValues = actualData.map((item) => item[1]);

      const xMean = xValues.reduce((a, b) => a + b, 0) / n;
      const yMean = yValues.reduce((a, b) => a + b, 0) / n;

      const slope = xValues.reduce((sum, x, i) => sum + (x - xMean) * (yValues[i] - yMean), 0) /
                    xValues.reduce((sum, x) => sum + (x - xMean) ** 2, 0);

      const intercept = yMean - slope * xMean;

      // Get current month info
      const now = new Date();
      const currentMonth = now.getMonth();
      const currentYear = now.getFullYear();

      // Format actual data with months and projection line connecting smoothly
      const formattedActual = actualData.map((item, index) => {
        const itemDate = new Date(item[0]);
        const projectedValue = slope * index + intercept;
        return [
          itemDate.toLocaleDateString('en-US', { year: '2-digit', month: 'short' }),
          item[1],
          projectedValue, // Add projection line through actual data for smooth transition
          null, // Remove forecast label
        ];
      });

      // Create projection data for next 4 months - extended
      const projectionData = [];
      for (let i = 1; i <= 6; i++) { // Extended to 6 months for better visibility
        const projectionMonth = new Date(currentYear, currentMonth + i, 1);
        const monthIndex = n - 1 + i;
        const projectedValue = Math.max(0, slope * monthIndex + intercept);
        projectionData.push([
          projectionMonth.toLocaleDateString('en-US', { year: '2-digit', month: 'short' }),
          null,
          projectedValue,
          null,
        ]);
      }

      return [
        [this.$t('pages.home.chartLabels.month'), this.$t('pages.home.chartLabels.actual'), this.$t('pages.home.chartLabels.projection'), { role: 'annotation' }],
        ...formattedActual,
        ...projectionData,
      ];
    },
  },
  methods: {
    ...mapActions(store, ['showSnackbar', 'showLoading', 'hideLoading']),

    async checkEmailVerificationStatus() {
      try {
        const response = await axios.get('/api/email-verification/status');

        // If user has 2FA enabled but email not verified, show dialog after 10 seconds
        if (response.data.should_verify) {
          this.verificationCheckTimeout = setTimeout(() => {
            this.showEmailVerificationDialog = true;
          }, 10000); // 10 seconds
        }
      } catch (error) {
        console.error('Error checking email verification status:', error);
      }
    },

    onEmailVerified() {
      this.showSnackbar('Email verified successfully!', 'success');
    },

    onVerificationSkipped() {
      // User skipped verification, could show a reminder later
      console.log('Email verification skipped');
    },

    formatCurrency(value) {
      return formatCurrency(value, this.settings);
    },

    formatDate(dateStr) {
      return formatDate(dateStr, this.settings);
    },

    formatHours(value) {
      return `${parseFloat(value).toFixed(1)}h`;
    },
    async fetchDashboardData() {
      // Show the loading overlay
      this.showLoading();

      try {
        const response = await axios.get("/api/dashboard");
        this.kpis = response.data.kpis;

        // Store hero trend data (common for both roles)
        this.heroTrendDataRaw = response.data.hero_trend_data || [];

        // Store data for both admin and freelancer roles
        if (this.isAdmin) {
          this.revenueByCustomer = response.data.revenue_by_customer || {};
          this.yearlyRevenueTrend = response.data.yearly_revenue_trend || [];
        } else {
          this.earningsByProject = response.data.earnings_by_project || {};
          this.yearlyEarningsTrend = response.data.yearly_earnings_trend || [];
        }

        // Common data for both roles
        this.monthlyHours = response.data.monthly_hours.map((item) => ({
          ...item,
          billable: parseFloat(item.billable || 0),
        }));

        this.upcomingDeadlines = response.data.upcoming_deadlines.map(
          (deadline) => ({
            ...deadline,
            days_until: Math.ceil(deadline.days_until),
          })
        );
      } catch (error) {
        console.error("Error fetching dashboard data:", error);
        this.showSnackbar("Failed to load dashboard data. Please try again later.", "error");
      } finally {
        // Hide the loading overlay when done
        this.hideLoading();
      }
    },
  },
  mounted() {
    this.fetchDashboardData();
    // Check if email verification is needed
    this.checkEmailVerificationStatus();
    // Refresh data every 5 minutes
    setInterval(this.fetchDashboardData, 300000);
  },

  beforeUnmount() {
    // Clean up timeout if component is destroyed
    if (this.verificationCheckTimeout) {
      clearTimeout(this.verificationCheckTimeout);
    }
  },
};
</script>

<style scoped>
/* Google Charts renders fixed-size divs; stretch them to the hero container */
.hero-chart :deep(> div),
.hero-chart :deep(svg) {
  width: 100% !important;
  height: 100% !important;
}
</style>
