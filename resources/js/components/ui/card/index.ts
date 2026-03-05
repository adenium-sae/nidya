import Card from './Card.vue'
import { cn } from '@/lib/utils'
import { h, defineComponent, type HTMLAttributes } from 'vue'

const CardHeader = defineComponent({
  props: { class: String as () => HTMLAttributes['class'] },
  setup(props, { slots }) {
    return () => h('div', { class: cn('flex flex-col space-y-1.5 p-6', props.class) }, slots.default?.())
  }
})

const CardTitle = defineComponent({
  props: { class: String as () => HTMLAttributes['class'] },
  setup(props, { slots }) {
    return () => h('h3', { class: cn('text-2xl font-semibold leading-none tracking-tight', props.class) }, slots.default?.())
  }
})

const CardDescription = defineComponent({
  props: { class: String as () => HTMLAttributes['class'] },
  setup(props, { slots }) {
    return () => h('p', { class: cn('text-sm text-muted-foreground', props.class) }, slots.default?.())
  }
})

const CardContent = defineComponent({
  props: { class: String as () => HTMLAttributes['class'] },
  setup(props, { slots }) {
    return () => h('div', { class: cn('p-6 pt-0', props.class) }, slots.default?.())
  }
})

const CardFooter = defineComponent({
  props: { class: String as () => HTMLAttributes['class'] },
  setup(props, { slots }) {
    return () => h('div', { class: cn('flex items-center p-6 pt-0', props.class) }, slots.default?.())
  }
})

export { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter }
