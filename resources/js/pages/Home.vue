<template>
  <div>
    <EmailVerificationDialog 
      v-model="showEmailVerificationDialog"
      @verified="onEmailVerified"
      @skipped="onVerificationSkipped"
    />
    
    <v-container fluid class="dashboard-container">
      <!-- Hero Section - This Month Focus -->
      <v-row class="">
        <v-col cols="12" class="pa-2">
          <div class="hero-section">
            <div class="hero-backdrop"></div>
            <v-row class="hero-content">
              <v-col cols="12" md="6" class="d-flex flex-column justify-center">
                <div class="hero-text">
                  <h1 class="text-h4 font-weight-bold mb-2">{{ $t('pages.home.yourMoneyToday') }}</h1>
                  <p class="text-subtitle-1 mb-6">{{ $t('pages.home.trackGrowth', { type: isAdmin ? 'revenue' : 'earnings' }) }}</p>
                  <div class="hero-stat">
                    <div class="hero-label">{{ isAdmin ? $t('pages.home.thisMonthRevenue') : $t('pages.home.thisMonthEarnings') }}</div>
                    <div class="hero-value animate-scale">{{ formatCurrency(isAdmin ? kpis.revenue.monthly.actual : kpis.earnings.monthly.actual) }}</div>
                    <div class="hero-meta" v-if="(isAdmin ? kpis.revenue.is_extrapolated : kpis.earnings.is_extrapolated)">
                      <v-icon size="small" color="warning" class="mr-1">mdi-lightning-bolt</v-icon>
                      <span>{{ $t('pages.home.extrapolatedEstimate') }}: {{ formatCurrency(isAdmin ? kpis.revenue.monthly.extrapolated : kpis.earnings.monthly.extrapolated) }}</span>
                    </div>
                  </div>
                </div>
              </v-col>
              <v-col cols="12" md="6" class="hero-chart-wrapper">
                <div class="hero-chart-container">
                  <GChart
                    type="LineChart"
                    :data="heroTrendData"
                    :options="heroTrendChartOptions"
                  />
                </div>
              </v-col>
            </v-row>
          </div>
        </v-col>
      </v-row>

      <!-- Key Metrics Row -->
      <v-row>
        <!-- This Year -->
        <v-col cols="12" sm="6" md="3" class="pa-2">
          <v-card class="kpi-card kpi-accent-primary" :elevation="0">
            <v-card-text class="kpi-card-content">
              <div class="kpi-icon-bg primary">
                <v-icon>mdi-calendar</v-icon>
              </div>
              <div class="kpi-label">{{ $t('pages.home.thisYear') }}</div>
              <div class="kpi-primary-value animate-counter">{{
                formatCurrency(isAdmin ? kpis.revenue.yearly.actual : kpis.earnings.yearly.actual)
              }}</div>
              <div class="kpi-meta mt-3">
                <div class="meta-row" v-if="(isAdmin ? kpis.revenue.yearly.extrapolated : kpis.earnings.yearly.extrapolated)">
                  <span class="meta-label">{{ $t('pages.home.estimated') }}:</span>
                  <span class="meta-value">{{ formatCurrency(isAdmin ? kpis.revenue.yearly.extrapolated : kpis.earnings.yearly.extrapolated) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.hours') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.yearly.actual) }}</span>
                </div>
                <div class="meta-row" v-if="kpis.hours.yearly.extrapolated && kpis.hours.yearly.extrapolated != kpis.hours.yearly.actual">
                  <span class="meta-label">{{ $t('pages.home.estimated') }} {{ $t('pages.home.hours') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.yearly.extrapolated) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.billable') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.yearly.actual_billable) }}</span>
                </div>
                <div class="meta-row" v-if="kpis.hours.yearly.extrapolated_billable && kpis.hours.yearly.extrapolated_billable != kpis.hours.yearly.actual_billable">
                  <span class="meta-label">{{ $t('pages.home.estimated') }} {{ $t('pages.home.billable') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.yearly.extrapolated_billable) }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- This Month - Featured -->
        <v-col cols="12" sm="6" md="3" class="pa-2">
          <v-card class="kpi-card kpi-featured" :elevation="0">
            <v-card-text class="kpi-card-content">
              <div class="kpi-icon-bg accent">
                <v-icon>mdi-lightning-bolt</v-icon>
              </div>
              <div class="kpi-label">{{ $t('pages.home.thisMonth') }}</div>
              <div class="kpi-primary-value animate-counter">{{
                formatCurrency(isAdmin ? kpis.revenue.monthly.actual : kpis.earnings.monthly.actual)
              }}</div>
              <div class="kpi-meta mt-3">
                <div class="meta-row" v-if="(isAdmin ? kpis.revenue.is_extrapolated : kpis.earnings.is_extrapolated)">
                  <span class="meta-label">{{ $t('pages.home.estimated') }}:</span>
                  <span class="meta-value">{{ formatCurrency(isAdmin ? kpis.revenue.monthly.extrapolated : kpis.earnings.monthly.extrapolated) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.totalHours') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.monthly.actual) }}</span>
                </div>
                <div class="meta-row" v-if="kpis.hours.monthly.extrapolated && kpis.hours.monthly.extrapolated != kpis.hours.monthly.actual">
                  <span class="meta-label">{{ $t('pages.home.estimated') }} {{ $t('pages.home.hours') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.monthly.extrapolated) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.billable') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.monthly.actual_billable) }}</span>
                </div>
                <div class="meta-row" v-if="kpis.hours.monthly.extrapolated_billable && kpis.hours.monthly.extrapolated_billable != kpis.hours.monthly.actual_billable">
                  <span class="meta-label">{{ $t('pages.home.estimated') }} {{ $t('pages.home.billable') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.monthly.extrapolated_billable) }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Last Month -->
        <v-col cols="12" sm="6" md="3" class="pa-2">
          <v-card class="kpi-card kpi-accent-success" :elevation="0">
            <v-card-text class="kpi-card-content">
              <div class="kpi-icon-bg success">
                <v-icon>mdi-history</v-icon>
              </div>
              <div class="kpi-label">{{ $t('pages.home.lastMonth') }}</div>
              <div class="kpi-primary-value">{{
                formatCurrency(isAdmin ? kpis.revenue.last_month.paid : kpis.earnings.last_month.paid)
              }}</div>
              <div class="kpi-meta mt-3">
                <div class="meta-row" v-if="(isAdmin ? kpis.revenue.last_month.due : kpis.earnings.last_month.due) > 0">
                  <span class="meta-label">{{ $t('common.due') }}:</span>
                  <span class="meta-value text-warning">{{ formatCurrency(isAdmin ? kpis.revenue.last_month.due : kpis.earnings.last_month.due) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.hours') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.last_month.total) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.billable') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.last_month.billable) }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Last Year -->
        <v-col cols="12" sm="6" md="3" class="pa-2">
          <v-card class="kpi-card kpi-accent-info" :elevation="0">
            <v-card-text class="kpi-card-content">
              <div class="kpi-icon-bg info">
                <v-icon>mdi-trending-up</v-icon>
              </div>
              <div class="kpi-label">{{ $t('pages.home.lastYear') }}</div>
              <div class="kpi-primary-value">{{
                formatCurrency(isAdmin ? kpis.revenue.last_year.paid : kpis.earnings.last_year.paid)
              }}</div>
              <div class="kpi-meta mt-3">
                <div class="meta-row" v-if="(isAdmin ? kpis.revenue.last_year.due : kpis.earnings.last_year.due) > 0">
                  <span class="meta-label">{{ $t('common.due') }}:</span>
                  <span class="meta-value text-warning">{{ formatCurrency(isAdmin ? kpis.revenue.last_year.due : kpis.earnings.last_year.due) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.hours') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.last_year.total) }}</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">{{ $t('pages.home.billable') }}:</span>
                  <span class="meta-value">{{ formatHours(kpis.hours.last_year.billable) }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

    <!-- Charts Row 1: Trends -->
    <v-row>
      <!-- Revenue/Earnings Trend Chart -->
      <v-col cols="12" md="8" class="pa-2">
        <v-card class="chart-card" :elevation="0">
          <v-card-title class="chart-title">
            <v-icon size="24" class="mr-2">mdi-chart-line-variant</v-icon>
            {{ isAdmin ? $t('pages.home.revenue') : $t('pages.home.earnings') }} {{ $t('pages.home.trend') }} ({{ new Date().getFullYear() }})
          </v-card-title>
          <v-card-text>
            <GChart
              type="LineChart"
              :data="isAdmin ? revenueChartData : earningsChartData"
              :options="isAdmin ? dynamicRevenueChartOptions : dynamicEarningsChartOptions"
            />
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Top Customers or Top Projects Chart -->
      <v-col cols="12" md="4" class="pa-2">
        <v-card class="chart-card" :elevation="0">
          <v-card-title class="chart-title">
            <v-icon size="24" class="mr-2">mdi-crown</v-icon>
            {{ isAdmin ? $t('pages.home.topCustomers') : $t('pages.home.topProjects') }}
          </v-card-title>
          <v-card-text>
            <GChart
              type="BarChart"
              :data="isAdmin ? customerChartData : projectEarningsChartData"
              :options="customerChartOptions"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts Row 2: Hours & Deadlines -->
    <v-row>
      <!-- Hours Worked Chart -->
      <v-col cols="12" md="8" class="pa-2">
        <v-card class="chart-card" :elevation="0">
          <v-card-title class="chart-title">
            <v-icon size="24" class="mr-2">mdi-clock-check</v-icon>
            {{ $t('pages.home.hoursWorkedThisMonth') }}
          </v-card-title>
          <v-card-text>
            <GChart
              type="ColumnChart"
              :data="hoursChartData"
              :options="hoursChartOptions"
            />
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Upcoming Deadlines -->
      <v-col cols="12" md="4" class="pa-2">
        <v-card class="chart-card" :elevation="0">
          <v-card-title class="chart-title">
            <v-icon size="24" class="mr-2">mdi-calendar-alert</v-icon>
            {{ $t('pages.home.upcomingDeadlines') }}
          </v-card-title>
          <v-card-text class="pb-0">
            <v-list class="deadline-list pb-0">
              <template v-if="upcomingDeadlines.length > 0">
                <v-list-item
                  v-for="(deadline, index) in upcomingDeadlines.slice(0, 5)"
                  :key="deadline.id"
                  class="deadline-item"
                >
                  <template v-slot:prepend>
                    <v-icon 
                      :color="deadline.days_until <= 3 ? 'error' : (deadline.days_until <= 7 ? 'warning' : 'success')"
                      class="mr-3"
                    >
                      {{ deadline.days_until <= 3 ? 'mdi-alert-circle' : 'mdi-clock-outline' }}
                    </v-icon>
                  </template>
                  <v-list-item-title class="font-weight-medium">{{ deadline.name }}</v-list-item-title>
                  <v-list-item-subtitle>{{ deadline.customer }} • {{ formatDate(deadline.deadline) }}</v-list-item-subtitle>
                  <template v-slot:append>
                    <v-chip
                      :color="deadline.days_until <= 3 ? 'error' : (deadline.days_until <= 7 ? 'warning' : 'success')"
                      size="small"
                      class="font-weight-bold"
                    >
                      {{ deadline.days_until }}{{ $t('pages.home.daysSuffix') }}
                    </v-chip>
                  </template>
                </v-list-item>
              </template>
              <v-list-item v-else class="text-center py-8">
                <v-icon size="48" color="rgba(255,255,255,0.2)" class="mb-2">mdi-check-all</v-icon>
                <div class="text-subtitle-2 text-center">{{ $t('pages.home.noUpcomingDeadlines') }}</div>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
  </div>
</template>

<script>
import { GChart } from "vue-google-charts";
import axios from "axios";
import { mapState, mapActions } from 'pinia';
import { store } from '../store';
import EmailVerificationDialog from '../components/EmailVerificationDialog.vue';
import { formatCurrency, formatDate } from '../utils/formatters';

export default {
  name: "Home",
  components: {
    GChart,
    EmailVerificationDialog,
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
          textStyle: { color: "rgba(255,255,255,0)" },
          gridlines: { color: "transparent" },
          baselineColor: "transparent",
        },
        hAxis: {
          textStyle: { color: "rgba(255,255,255,0)" },
          gridlines: { color: "transparent" },
          baselineColor: "transparent",
        },
        series: {
          0: {
            color: "#64B5F6",
            lineWidth: 3,
            pointSize: 5,
            lineDashStyle: [1, 0], // Solid line for actual
          },
          1: {
            color: "#FFB74D",
            lineWidth: 2,
            pointSize: 0,
            lineDashStyle: [5, 5], // Dashed line for projection
          },
        },
        annotations: {
          alwaysOutside: true,
          textStyle: {
            fontSize: 10,
            color: '#FFB74D',
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
        legend: { position: "top", textStyle: { color: "#fff", fontSize: 13 } },
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
          textStyle: { color: "rgba(255,255,255,0.7)", fontSize: 11 },
          gridlines: {
            color: "rgba(100, 181, 246, 0.1)",
            interval: 1,
          },
        },
        hAxis: {
          textStyle: { color: "rgba(255,255,255,0.7)", fontSize: 11 },
          gridlines: {
            color: "rgba(100, 181, 246, 0.05)",
            interval: 1,
          },
          showTextEvery: 7,
          format: "MMM",
        },
        series: {
          0: {
            color: "#64B5F6",
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
          textStyle: { color: "#fff", fontSize: 12 },
          gridlines: { color: "rgba(255,255,255,0.1)" },
          baselineColor: "transparent",
        },
        vAxis: {
          textStyle: { color: "#fff", fontSize: 12 },
          gridlines: { color: "transparent" },
          baselineColor: "transparent",
        },
        colors: ["#64B5F6"],
        animation: {
          duration: 1000,
          easing: 'inAndOut',
          startup: true,
        },
      },
      hoursChartOptions: {
        legend: {
          position: "top",
          textStyle: { color: "#fff", fontSize: 13 },
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
          textStyle: { color: "rgba(255,255,255,0.7)", fontSize: 11 },
          gridlines: { color: "rgba(100, 181, 246, 0.1)" },
        },
        hAxis: {
          textStyle: { color: "rgba(255,255,255,0.7)", fontSize: 11 },
          gridlines: { color: "transparent" },
          showTextEvery: 2,
        },
        series: {
          0: { 
            color: "#64B5F6",
            targetAxisIndex: 0,
          },
          1: { 
            color: "#81C784",
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
.dashboard-container {
  padding-top: 16px;
  padding-bottom: 32px;
}

/* Hero Section */
.hero-section {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(100, 181, 246, 0.15) 0%, rgba(129, 199, 132, 0.15) 100%);
  border: 2px solid rgba(100, 181, 246, 0.3);
  min-height: 220px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.hero-section:hover {
  border: 2px solid rgba(100, 181, 246, 0.5);
  background: linear-gradient(135deg, rgba(100, 181, 246, 0.2) 0%, rgba(129, 199, 132, 0.2) 100%);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.hero-backdrop {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(100, 181, 246, 0.1) 0%, transparent 70%);
  border-radius: 50%;
  filter: blur(40px);
  animation: float 6s ease-in-out infinite;
}

.hero-content {
  position: relative;
  z-index: 1;
  padding: 32px;
}

.hero-text {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.hero-text h1 {
  background: linear-gradient(135deg, #64B5F6 0%, #81C784 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 8px;
}

.hero-text p {
  color: rgba(255, 255, 255, 0.7);
}

.hero-stat {
  margin-top: 16px;
}

.hero-label {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 8px;
}

.hero-value {
  font-size: 48px;
  font-weight: 800;
  background: linear-gradient(135deg, #64B5F6 0%, #81C784 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 12px;
}

.hero-meta {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: rgba(255, 200, 100, 0.9);
  font-weight: 500;
}

.hero-chart-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-chart-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 200px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  border: 2px solid rgba(100, 181, 246, 0.15);
  padding: 0;
  overflow: hidden;
}

.hero-chart-container > div {
  width: 100% !important;
  height: 100% !important;
}

.hero-chart-container svg {
  width: 100% !important;
  height: 100% !important;
}

/* KPI Cards */
.kpi-card {
  height: 220px;
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 100% 0%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.kpi-card:hover {
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.06);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.kpi-card:hover::before {
  opacity: 1;
}

.kpi-card-content {
  height: 100%;
  display: flex;
  flex-direction: column;
  position: relative;
  z-index: 2;
  padding: 16px !important;
}

.kpi-icon-bg {
  position: absolute;
  top: -20px;
  right: -10px;
  font-size: 120px;
  opacity: 0.08;
  z-index: 0;
  pointer-events: none;
  margin-right: 16px;
  margin-top: 16px;
}

.kpi-icon-bg.primary {
  color: #64B5F6;
}

.kpi-icon-bg.accent {
  color: #FFB74D;
}

.kpi-icon-bg.success {
  color: #81C784;
}

.kpi-icon-bg.info {
  color: #4DD0E1;
}

.kpi-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 8px;
}

.kpi-primary-value {
  font-size: 28px;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
}

.kpi-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meta-row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}

.meta-label {
  color: rgba(255, 255, 255, 0.5);
}

.meta-value {
  color: rgba(255, 255, 255, 0.85);
  font-weight: 600;
}

/* Chart Cards */
.chart-card {
  height: auto;
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.chart-card:hover {
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.05);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.chart-title {
  color: #fff;
  font-weight: 600;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, rgba(100, 181, 246, 0.1) 0%, transparent 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.v-card-text {
  color: rgba(255, 255, 255, 0.87);
}

/* Deadline List Styling */
.deadline-list {
  background: transparent;
}

.deadline-item {
  background: rgba(255, 255, 255, 0.05);
  margin-bottom: 8px;
  border-radius: 10px;
  border-left: 4px solid transparent;
  transition: all 0.3s ease;
  padding: 12px;
}

.deadline-item:hover {
  background: rgba(255, 255, 255, 0.08);
  border-left: 4px solid #64B5F6;
}

.v-list-item-title {
  color: #fff !important;
  font-weight: 600;
}

.v-list-item-subtitle {
  color: rgba(255, 255, 255, 0.6) !important;
}

.v-chip {
  font-weight: 600;
}

/* Animations */
@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 4px 16px rgba(255, 167, 38, 0.4);
  }
  50% {
    box-shadow: 0 4px 24px rgba(255, 167, 38, 0.6);
  }
}

@keyframes scale-in {
  0% {
    opacity: 0;
    transform: scale(0.95);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes count-up {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}

.animate-scale {
  animation: scale-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.animate-counter {
  animation: count-up 0.8s ease-out;
}

/* Responsive */
@media (max-width: 960px) {
  .hero-value {
    font-size: 36px;
  }

  .hero-section {
    min-height: auto;
  }

  .hero-content {
    padding: 24px;
  }
}

@media (max-width: 600px) {
  .kpi-card {
    height: auto;
  }

  .hero-value {
    font-size: 28px;
  }

  .hero-text h1 {
    font-size: 24px !important;
  }

  .hero-backdrop {
    display: none;
  }
}
</style>
