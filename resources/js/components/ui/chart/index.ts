export { default as ChartCrosshair } from "./ChartCrosshair.vue"
export { default as ChartLegend } from "./ChartLegend.vue"
export { default as ChartSingleTooltip } from "./ChartSingleTooltip.vue"
export { default as ChartTooltip } from "./ChartTooltip.vue"
export { default as AreaChart } from "./AreaChart.vue"
export { default as LineChart } from "./LineChart.vue"

export function defaultColors(count: number = 3) {
  const colors = []
  for (let i = 0; i < count; i++) {
    colors.push(`hsl(var(--chart-${(i % 5) + 1}))`)
  }
  return colors
}

export * from "./interface"
