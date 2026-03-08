<template>
  <div class="space-y-6">
    <div ref="chartTopUpsRef" class="h-80 w-full" />
    <div ref="chartFeesRef" class="h-80 w-full" />
    <div ref="chartPlatformRef" class="h-80 w-full" />
  </div>
</template>

<script setup>
const props = defineProps({
  topUps: { type: Object, default: () => ({ dates: [], values: [] }) },
  consultationFees: { type: Object, default: () => ({ dates: [], values: [] }) },
  platformRevenue: { type: Object, default: () => ({ dates: [], values: [] }) }
})

const chartTopUpsRef = ref(null)
const chartFeesRef = ref(null)
const chartPlatformRef = ref(null)
let chartTopUps = null
let chartFees = null
let chartPlatform = null

function renderCharts () {
  if (typeof window === 'undefined') return
  import('echarts').then((echarts) => {
    const e = echarts.default
    const dates = props.topUps?.dates ?? []
    const shortDates = dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d))

    if (chartTopUpsRef.value) {
      chartTopUps = e.init(chartTopUpsRef.value)
      chartTopUps.setOption({
        title: { text: 'Patient top-ups', left: 0, textStyle: { fontSize: 14 } },
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', data: shortDates, boundaryGap: false },
        yAxis: { type: 'value', name: 'Amount' },
        series: [{ name: 'Top-ups', type: 'line', smooth: true, data: props.topUps?.values ?? [], areaStyle: {} }]
      })
    }
    if (chartFeesRef.value) {
      chartFees = e.init(chartFeesRef.value)
      chartFees.setOption({
        title: { text: 'Consultation fees', left: 0, textStyle: { fontSize: 14 } },
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', data: shortDates, boundaryGap: false },
        yAxis: { type: 'value', name: 'Amount' },
        series: [{ name: 'Fees', type: 'line', smooth: true, data: props.consultationFees?.values ?? [], areaStyle: {} }]
      })
    }
    if (chartPlatformRef.value) {
      chartPlatform = e.init(chartPlatformRef.value)
      chartPlatform.setOption({
        title: { text: 'Platform revenue', left: 0, textStyle: { fontSize: 14 } },
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', data: shortDates, boundaryGap: false },
        yAxis: { type: 'value', name: 'Amount' },
        series: [{ name: 'Platform', type: 'line', smooth: true, data: props.platformRevenue?.values ?? [], areaStyle: {} }]
      })
    }
  }).catch(() => {})
}

onMounted(() => {
  nextTick(() => renderCharts())
})

watch(() => [props.topUps, props.consultationFees, props.platformRevenue], () => {
  nextTick(() => {
    if (chartTopUps && props.topUps?.dates) {
      chartTopUps.setOption({
        xAxis: { data: props.topUps.dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d)) },
        series: [{ data: props.topUps.values }]
      })
    }
    if (chartFees && props.consultationFees?.dates) {
      chartFees.setOption({
        xAxis: { data: props.consultationFees.dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d)) },
        series: [{ data: props.consultationFees.values }]
      })
    }
    if (chartPlatform && props.platformRevenue?.dates) {
      chartPlatform.setOption({
        xAxis: { data: props.platformRevenue.dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d)) },
        series: [{ data: props.platformRevenue.values }]
      })
    }
  })
}, { deep: true })

onBeforeUnmount(() => {
  chartTopUps?.dispose()
  chartFees?.dispose()
  chartPlatform?.dispose()
})
</script>
