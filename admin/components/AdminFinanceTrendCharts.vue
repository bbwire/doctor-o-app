<template>
  <div v-if="hasAnyData" class="space-y-8">
    <div v-if="topUps?.dates?.length" class="w-full">
      <div ref="chartTopUpsRef" class="h-80 w-full" />
    </div>
    <div v-if="consultationRevenue?.dates?.length" class="w-full">
      <div ref="chartFeesRef" class="h-80 w-full" />
    </div>
    <div v-if="platformRevenue?.dates?.length" class="w-full">
      <div ref="chartPlatformRef" class="h-80 w-full" />
    </div>
    <div v-if="doctorEarnings?.dates?.length" class="w-full">
      <div ref="chartDoctorRef" class="h-80 w-full" />
    </div>
    <div v-if="institutionRevenue?.dates?.length" class="w-full">
      <div ref="chartInstitutionRef" class="h-80 w-full" />
    </div>
  </div>
  <p v-else class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
    No trend data yet. Data will appear as transactions are recorded.
  </p>
</template>

<script setup>
import * as echarts from 'echarts'

const props = defineProps({
  topUps: { type: Object, default: () => ({ dates: [], values: [] }) },
  consultationRevenue: { type: Object, default: () => ({ dates: [], values: [] }) },
  platformRevenue: { type: Object, default: () => ({ dates: [], values: [] }) },
  doctorEarnings: { type: Object, default: () => ({ dates: [], values: [] }) },
  institutionRevenue: { type: Object, default: () => ({ dates: [], values: [] }) }
})

const chartTopUpsRef = ref(null)
const chartFeesRef = ref(null)
const chartPlatformRef = ref(null)
const chartDoctorRef = ref(null)
const chartInstitutionRef = ref(null)
let chartTopUps = null
let chartFees = null
let chartPlatform = null
let chartDoctor = null
let chartInstitution = null

const hasAnyData = computed(() =>
  (props.topUps?.dates?.length) || (props.consultationRevenue?.dates?.length) ||
  (props.platformRevenue?.dates?.length) || (props.doctorEarnings?.dates?.length) ||
  (props.institutionRevenue?.dates?.length)
)

function renderCharts () {
  if (typeof window === 'undefined') return
  
  // Clear existing charts
  if (chartTopUps) {
    chartTopUps.dispose()
    chartTopUps = null
  }
  if (chartFees) {
    chartFees.dispose()
    chartFees = null
  }
  if (chartPlatform) {
    chartPlatform.dispose()
    chartPlatform = null
  }
  if (chartDoctor) {
    chartDoctor.dispose()
    chartDoctor = null
  }
  if (chartInstitution) {
    chartInstitution.dispose()
    chartInstitution = null
  }

  const dates = props.topUps?.dates ?? []
  const shortDates = dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d))

  if (chartTopUpsRef.value && props.topUps?.dates?.length) {
    chartTopUps = echarts.init(chartTopUpsRef.value)
    chartTopUps.setOption({
      backgroundColor: 'transparent',
      title: { text: 'Patient top-ups', left: 0, textStyle: { fontSize: 14, color: '#6b7280' } },
      tooltip: { 
        trigger: 'axis',
        backgroundColor: '#1f2937',
        borderColor: '#374151',
        textStyle: { color: '#f3f4f6' }
      },
      xAxis: { 
        type: 'category', 
        data: shortDates, 
        boundaryGap: false,
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' }
      },
      yAxis: { 
        type: 'value', 
        name: 'Amount',
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' },
        nameTextStyle: { color: '#9ca3af' }
      },
      series: [{ 
        name: 'Top-ups', 
        type: 'line', 
        smooth: true, 
        data: props.topUps?.values ?? [], 
        areaStyle: { 
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(34, 197, 94, 0.3)' },
              { offset: 1, color: 'rgba(34, 197, 94, 0.1)' }
            ]
          }
        },
        itemStyle: { color: '#22c55e' },
        lineStyle: { color: '#22c55e' }
      }]
    })
  }
  if (chartFeesRef.value && props.consultationRevenue?.dates?.length) {
    chartFees = echarts.init(chartFeesRef.value)
    chartFees.setOption({
      backgroundColor: 'transparent',
      title: { text: 'Consultation Revenue', left: 0, textStyle: { fontSize: 14, color: '#6b7280' } },
      tooltip: { 
        trigger: 'axis',
        backgroundColor: '#1f2937',
        borderColor: '#374151',
        textStyle: { color: '#f3f4f6' }
      },
      xAxis: { 
        type: 'category', 
        data: shortDates, 
        boundaryGap: false,
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' }
      },
      yAxis: { 
        type: 'value', 
        name: 'Amount',
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' },
        nameTextStyle: { color: '#9ca3af' }
      },
      series: [{ 
        name: 'Fees', 
        type: 'line', 
        smooth: true, 
        data: props.consultationRevenue?.values ?? [], 
        areaStyle: { 
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(34, 197, 94, 0.3)' },
              { offset: 1, color: 'rgba(34, 197, 94, 0.1)' }
            ]
          }
        },
        itemStyle: { color: '#22c55e' },
        lineStyle: { color: '#22c55e' }
      }]
    })
  }
  if (chartPlatformRef.value && props.platformRevenue?.dates?.length) {
    chartPlatform = echarts.init(chartPlatformRef.value)
    chartPlatform.setOption({
      backgroundColor: 'transparent',
      title: { text: 'Platform Revenue', left: 0, textStyle: { fontSize: 14, color: '#6b7280' } },
      tooltip: { 
        trigger: 'axis',
        backgroundColor: '#1f2937',
        borderColor: '#374151',
        textStyle: { color: '#f3f4f6' }
      },
      xAxis: { 
        type: 'category', 
        data: shortDates, 
        boundaryGap: false,
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' }
      },
      yAxis: { 
        type: 'value', 
        name: 'Amount',
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' },
        nameTextStyle: { color: '#9ca3af' }
      },
      series: [{ 
        name: 'Platform', 
        type: 'line', 
        smooth: true, 
        data: props.platformRevenue?.values ?? [], 
        areaStyle: { 
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(34, 197, 94, 0.3)' },
              { offset: 1, color: 'rgba(34, 197, 94, 0.1)' }
            ]
          }
        },
        itemStyle: { color: '#22c55e' },
        lineStyle: { color: '#22c55e' }
      }]
    })
  }
  if (chartDoctorRef.value && props.doctorEarnings?.dates?.length) {
    chartDoctor = echarts.init(chartDoctorRef.value)
    chartDoctor.setOption({
      backgroundColor: 'transparent',
      title: { text: 'Doctor Earnings', left: 0, textStyle: { fontSize: 14, color: '#6b7280' } },
      tooltip: { 
        trigger: 'axis',
        backgroundColor: '#1f2937',
        borderColor: '#374151',
        textStyle: { color: '#f3f4f6' }
      },
      xAxis: { 
        type: 'category', 
        data: shortDates, 
        boundaryGap: false,
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' }
      },
      yAxis: { 
        type: 'value', 
        name: 'Amount',
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' },
        nameTextStyle: { color: '#9ca3af' }
      },
      series: [{ 
        name: 'Doctor earnings', 
        type: 'line', 
        smooth: true, 
        data: props.doctorEarnings?.values ?? [], 
        areaStyle: { 
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(34, 197, 94, 0.3)' },
              { offset: 1, color: 'rgba(34, 197, 94, 0.1)' }
            ]
          }
        },
        itemStyle: { color: '#22c55e' },
        lineStyle: { color: '#22c55e' }
      }]
    })
  }
  if (chartInstitutionRef.value && props.institutionRevenue?.dates?.length) {
    chartInstitution = echarts.init(chartInstitutionRef.value)
    chartInstitution.setOption({
      backgroundColor: 'transparent',
      title: { text: 'Institution Revenue', left: 0, textStyle: { fontSize: 14, color: '#6b7280' } },
      tooltip: {
        trigger: 'axis',
        backgroundColor: '#1f2937',
        borderColor: '#374151',
        textStyle: { color: '#f3f4f6' }
      },
      xAxis: {
        type: 'category',
        data: shortDates,
        boundaryGap: false,
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' }
      },
      yAxis: {
        type: 'value',
        name: 'Amount',
        axisLine: { lineStyle: { color: '#374151' } },
        axisLabel: { color: '#9ca3af' },
        nameTextStyle: { color: '#9ca3af' }
      },
      series: [{
        name: 'Institution Revenue',
        type: 'line',
        smooth: true,
        data: props.institutionRevenue?.values ?? [],
        areaStyle: {
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(34, 197, 94, 0.3)' },
              { offset: 1, color: 'rgba(34, 197, 94, 0.1)' }
            ]
          }
        },
        itemStyle: { color: '#22c55e' },
        lineStyle: { color: '#22c55e' }
      }]
    })
  }
}

onMounted(() => {
  nextTick(() => renderCharts())
})

watch(() => [props.topUps, props.consultationRevenue, props.platformRevenue, props.doctorEarnings, props.institutionRevenue], () => {
  nextTick(() => {
    const isDark = document.documentElement.classList.contains('dark')
    const textColor = isDark ? '#f3f4f6' : '#374151'
    const gridColor = isDark ? '#374151' : '#e5e7eb'
    const labelColor = isDark ? '#9ca3af' : '#6b7280'
    
    if (chartTopUps && props.topUps?.dates) {
      chartTopUps.setOption({
        xAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor } },
        yAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor }, nameTextStyle: { color: labelColor } },
        title: { textStyle: { color: '#6b7280' } },
        tooltip: { backgroundColor: '#1f2937', borderColor: '#374151', textStyle: { color: '#f3f4f6' } }
      })
    }
    if (chartFees && props.consultationRevenue?.dates) {
      chartFees.setOption({
        xAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor } },
        yAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor }, nameTextStyle: { color: labelColor } },
        title: { textStyle: { color: '#6b7280' } },
        tooltip: { backgroundColor: '#1f2937', borderColor: '#374151', textStyle: { color: '#f3f4f6' } }
      })
    }
    if (chartPlatform && props.platformRevenue?.dates) {
      chartPlatform.setOption({
        xAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor } },
        yAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor }, nameTextStyle: { color: labelColor } },
        title: { textStyle: { color: '#6b7280' } },
        tooltip: { backgroundColor: '#1f2937', borderColor: '#374151', textStyle: { color: '#f3f4f6' } }
      })
    }
    if (chartDoctor && props.doctorEarnings?.dates) {
      chartDoctor.setOption({
        xAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor } },
        yAxis: { axisLine: { lineStyle: { color: gridColor } }, axisLabel: { color: labelColor }, nameTextStyle: { color: labelColor } },
        title: { textStyle: { color: '#6b7280' } },
        tooltip: { backgroundColor: '#1f2937', borderColor: '#374151', textStyle: { color: '#f3f4f6' } }
      })
    }
    if (chartInstitution && props.institutionRevenue?.dates) {
      chartInstitution.setOption({
        xAxis: { data: props.institutionRevenue.dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d)) },
        series: [{ data: props.institutionRevenue.values }]
      })
    }
  })
}, { deep: true })

onBeforeUnmount(() => {
  chartTopUps?.dispose()
  chartFees?.dispose()
  chartPlatform?.dispose()
  chartDoctor?.dispose()
  chartInstitution?.dispose()
})
</script>
