<template>
  <v-container fluid>
    <v-row>
      <!-- Last Year -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card">
          <v-card-text>
            <div class="d-flex align-center">
              <div class="text-h6">Last Year</div>
              <v-tooltip location="top">
                <template v-slot:activator="{ props }">
                  <v-icon v-bind="props" size="small" class="ml-1"
                    >mdi-information</v-icon
                  >
                </template>
                <span>Last year's revenue and hours</span>
              </v-tooltip>
            </div>
            <div class="mt-2">
              <div class="d-flex justify-space-between">
                <span>Revenue:</span>
                <span class="font-weight-bold">{{
                  formatCurrency(kpis.revenue.last_year)
                }}</span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Total Hours:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.last_year.total)
                }}</span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Billable:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.last_year.billable)
                }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- This Year -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card">
          <v-card-text>
            <div class="d-flex align-center">
              <div class="text-h6">This Year</div>
              <v-tooltip location="top">
                <template v-slot:activator="{ props }">
                  <v-icon v-bind="props" size="small" class="ml-1"
                    >mdi-information</v-icon
                  >
                </template>
                <span>Current year's revenue and hours</span>
              </v-tooltip>
            </div>
            <div class="mt-2">
              <div class="d-flex justify-space-between">
                <span>Revenue:</span>
                <span class="font-weight-bold">{{
                  formatCurrency(kpis.revenue.yearly)
                }}</span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Total Hours:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.yearly.total)
                }}</span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Billable:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.yearly.billable)
                }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Last Month -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card">
          <v-card-text>
            <div class="d-flex align-center">
              <div class="text-h6">Last Month</div>
              <v-tooltip location="top">
                <template v-slot:activator="{ props }">
                  <v-icon v-bind="props" size="small" class="ml-1"
                    >mdi-information</v-icon
                  >
                </template>
                <span>Last month's actual revenue and hours</span>
              </v-tooltip>
            </div>
            <div class="mt-2">
              <div class="d-flex justify-space-between">
                <span>Revenue:</span>
                <span class="font-weight-bold">{{
                  formatCurrency(kpis.revenue.last_month)
                }}</span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Total Hours:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.last_month.total)
                }}</span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Billable:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.last_month.billable)
                }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- This Month -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="kpi-card">
          <v-card-text>
            <div class="d-flex align-center">
              <div class="text-h6">This Month</div>
              <v-tooltip location="top">
                <template v-slot:activator="{ props }">
                  <v-icon v-bind="props" size="small" class="ml-1"
                    >mdi-information</v-icon
                  >
                </template>
                <span>Current month's extrapolated revenue and hours</span>
              </v-tooltip>
            </div>
            <div class="mt-2">
              <div class="d-flex justify-space-between">
                <span>Revenue:</span>
                <span class="font-weight-bold">
                  {{ formatCurrency(kpis.revenue.monthly) }}
                  <v-tooltip v-if="kpis.revenue.is_extrapolated" location="top">
                    <template v-slot:activator="{ props }">
                      <v-icon v-bind="props" size="small" color="warning"
                        >mdi-alert</v-icon
                      >
                    </template>
                    <span>Extrapolated based on current day of month</span>
                  </v-tooltip>
                </span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Total Hours:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.monthly.total)
                }}</span>
              </div>
              <div class="d-flex justify-space-between">
                <span>Billable:</span>
                <span class="font-weight-bold">{{
                  formatHours(kpis.hours.monthly.billable)
                }}</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <!-- Revenue Trend Chart -->
      <v-col cols="12" md="8">
        <v-card class="chart-card">
          <v-card-title
            >Revenue Trend ({{ new Date().getFullYear() }})</v-card-title
          >
          <v-card-text>
            <GChart
              type="LineChart"
              :data="revenueChartData"
              :options="revenueChartOptions"
            />
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Revenue by Customer Chart -->
      <v-col cols="12" md="4">
        <v-card class="chart-card">
          <v-card-title>Revenue by Customer</v-card-title>
          <v-card-text>
            <GChart
              type="PieChart"
              :data="customerChartData"
              :options="customerChartOptions"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <!-- Hours Worked Chart -->
      <v-col cols="12" md="8">
        <v-card class="chart-card">
          <v-card-title>Hours Worked (This Month)</v-card-title>
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
      <v-col cols="12" md="4">
        <v-card class="chart-card">
          <v-card-title>Upcoming Deadlines</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item
                v-for="deadline in upcomingDeadlines"
                :key="deadline.id"
                :title="deadline.name"
                :subtitle="deadline.customer"
              >
                <template v-slot:append>
                  <v-chip
                    :color="deadline.days_until <= 3 ? 'error' : 'warning'"
                    size="small"
                  >
                    {{ deadline.days_until }} days
                  </v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { GChart } from "vue-google-charts";
import axios from "axios";

export default {
  name: "Home",
  components: {
    GChart,
  },
  data() {
    return {
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
      },
      revenueByCustomer: {},
      yearlyRevenueTrend: [],
      monthlyHours: [],
      upcomingDeadlines: [],
      revenueChartOptions: {
        curveType: "function",
        legend: { position: "top", textStyle: { color: "#fff" } },
        height: 300,
        backgroundColor: "transparent",
        chartArea: {
          backgroundColor: "transparent",
          left: "5%",
          right: "5%",
          top: "15%",
          bottom: "10%",
        },
        vAxis: {
          format: "currency",
          textStyle: { color: "#fff" },
          gridlines: {
            color: "rgba(255,255,255,0.05)",
            interval: 1,
          },
        },
        hAxis: {
          textStyle: { color: "#fff" },
          gridlines: {
            color: "rgba(255,255,255,0.05)",
            interval: 1,
          },
            // slantedText: true,
          //   slantedTextAngle: 45,
          showTextEvery: 7,
          format: "dd MMM",
        },

        series: {
          0: {
            color: "#64B5F6",
            lineWidth: 3,
            pointSize: 6,
          },
        },
      },
      customerChartOptions: {
        legend: {
          position: "right",
          textStyle: { color: "#fff" },
        },
        height: 300,
        backgroundColor: "transparent",
        chartArea: {
          backgroundColor: "transparent",
          left: "5%",
          right: "15%",
          top: "5%",
          bottom: "5%",
        },
        pieHole: 0.4,
        colors: [
          "#64B5F6",
          "#81C784",
          "#FFB74D",
          "#E57373",
          "#BA68C8",
          "#4DD0E1",
          "#FFD54F",
          "#A1887F",
        ],
        pieSliceTextStyle: {
          color: "#fff",
        },
      },
      hoursChartOptions: {
        legend: {
          position: "top",
          textStyle: { color: "#fff" },
        },
        height: 300,
        backgroundColor: "transparent",
        chartArea: {
          backgroundColor: "transparent",
          left: "5%",
          right: "5%",
          top: "15%",
          bottom: "10%",
        },
        isStacked: true,
        vAxis: {
          format: "#.#h",
          textStyle: { color: "#fff" },
          gridlines: { color: "rgba(255,255,255,0.1)" },
        },
        hAxis: {
          textStyle: { color: "#fff" },
          gridlines: { color: "rgba(255,255,255,0.1)" },
        },
        series: {
          0: { color: "#64B5F6" }, // Billable
          1: { color: "#81C784" }, // Non-billable
        },
      },
    };
  },
  computed: {
    revenueChartData() {
      const headers = ["Date", "Revenue"];
      const data = this.yearlyRevenueTrend.map((item) => [
        new Date(item.date),
        parseFloat(item.amount),
      ]);
      return [headers, ...data];
    },
    customerChartData() {
      const headers = ["Customer", "Revenue"];
      const data = Object.entries(this.revenueByCustomer).map(
        ([customer, amount]) => [customer, parseFloat(amount)]
      );
      return [headers, ...data];
    },
    hoursChartData() {
      const headers = ["Date", "Billable", "Non-Billable"];
      const data = this.monthlyHours.map((item) => {
        const total = parseFloat(item.hours);
        const billable = parseFloat(item.billable || 0);
        const nonBillable = total - billable;
        return [new Date(item.date), billable, nonBillable];
      });
      return [headers, ...data];
    },
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("de-DE", {
        style: "currency",
        currency: "EUR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(parseFloat(value));
    },
    formatHours(value) {
      return `${parseFloat(value).toFixed(1)}h`;
    },
    async fetchDashboardData() {
      try {
        const response = await axios.get("/api/dashboard");
        this.kpis = response.data.kpis;
        this.revenueByCustomer = response.data.revenue_by_customer;
        this.yearlyRevenueTrend = response.data.yearly_revenue_trend;
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
      }
    },
  },
  mounted() {
    this.fetchDashboardData();
    // Refresh data every 5 minutes
    setInterval(this.fetchDashboardData, 300000);
  },
};
</script>

<style scoped>
.kpi-card {
  height: 160px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: transform 0.2s ease-in-out;
}

.kpi-card:hover {
  transform: translateY(-2px);
}

.chart-card {
  height: 400px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: transform 0.2s ease-in-out;
}

.chart-card:hover {
  transform: translateY(-2px);
}

.v-card-title {
  color: #fff;
  font-weight: 500;
  letter-spacing: 0.5px;
}

.v-card-text {
  color: rgba(255, 255, 255, 0.87);
}

.v-list {
  background: transparent;
}

.v-list-item {
  background: rgba(255, 255, 255, 0.05);
  margin-bottom: 8px;
  border-radius: 8px;
  transition: background-color 0.2s ease-in-out;
}

.v-list-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.v-list-item-title {
  color: #fff;
}

.v-list-item-subtitle {
  color: rgba(255, 255, 255, 0.6);
}

.v-chip {
  font-weight: 500;
}
</style>
