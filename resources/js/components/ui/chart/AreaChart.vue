<script setup lang="ts" generic="T extends Record<string, any>">
import { VisArea, VisAxis, VisLine, VisXYContainer } from "@unovis/vue"
import { type Spacing } from "@unovis/ts"
import { computed, type Component, ref } from "vue"
import { ChartCrosshair, ChartLegend, ChartSingleTooltip, defaultColors, type BaseChartProps } from "."

const props = withDefaults(defineProps<BaseChartProps<T> & {
  /**
   * Sets the type of the curve for the line and area.
   */
  curveType?: "basis" | "cardinal" | "catmullRom" | "linear" | "monotoneX" | "monotoneY" | "natural" | "step" | "stepAfter" | "stepBefore"
  /**
   * Controls the visibility of gradient.
   * @default true
   */
  showGradient?: boolean
}>(), {
  curveType: "monotoneX",
  filterOpacity: 0.2,
  margin: () => ({ top: 10, bottom: 30, left: 40, right: 10 }),
  showXAxis: true,
  showYAxis: true,
  showTooltip: true,
  showLegend: true,
  showGridLine: true,
  showGradient: true,
})

const containerRef = ref<HTMLElement>()

const x = (d: T, i: number) => i
const y = props.categories.map(category => (d: T) => d[category])

const color = props.colors?.length ? props.colors : defaultColors(props.categories.length)
const legendItems = props.categories.map((category, i) => ({ name: category, color: color[i] }))
</script>

<template>
  <div ref="containerRef" :class="[$attrs.class, 'w-full h-full flex flex-col items-end']">
    <ChartLegend v-if="showLegend" v-model:items="legendItems" />

    <VisXYContainer
      :data="data"
      :style="{ height: '100%', width: '100%' }"
      :margin="margin"
    >
      <svg width="0" height="0">
        <defs>
          <linearGradient v-for="(c, i) in color" :id="`line-gradient-${i}`" :key="i" x1="0" x2="0" y1="0" y2="1">
            <stop offset="5%" :stop-color="c" stop-opacity="0.3" />
            <stop offset="95%" :stop-color="c" stop-opacity="0" />
          </linearGradient>
        </defs>
      </svg>

      <template v-for="(category, i) in categories" :key="category">
        <VisArea
          :x="x"
          :y="y[i]"
          :curve-type="curveType"
          :color="`url(#line-gradient-${i})`"
          :opacity="filterOpacity"
        />
        <VisLine
          :x="x"
          :y="y[i]"
          :curve-type="curveType"
          :color="color[i]"
        />
      </template>

      <VisAxis
        v-if="showXAxis"
        type="x"
        :tick-format="xFormatter"
        :grid-line="showGridLine"
      />
      <VisAxis
        v-if="showYAxis"
        type="y"
        :tick-format="yFormatter"
        :grid-line="showGridLine"
      />

      <slot />

      <ChartSingleTooltip
        v-if="showTooltip"
        selector=".vis-line-path"
        :index="index"
        :items="legendItems"
        :value-formatter="yFormatter"
      />

      <ChartCrosshair
        v-if="showTooltip"
        :index="index"
        :items="legendItems"
        :colors="color"
      />
    </VisXYContainer>
  </div>
</template>
