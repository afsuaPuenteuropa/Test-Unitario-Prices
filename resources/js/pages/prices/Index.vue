<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    BadgePercent,
    CheckCircle2,
    FlaskConical,
    RefreshCw,
    Sparkles,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PublicLayout from '@/layouts/PublicLayout.vue';

type CurrencyCode = 'EUR' | 'USD';

type Currency = {
    code: CurrencyCode;
    name: string;
    symbol: string;
};

type ConversionResult = {
    amount: number;
    from: CurrencyCode;
    to: CurrencyCode;
    rate: number;
    converted: number;
};

type DiscountResult = {
    price: number;
    percentage: number;
    discount: number;
    final_price: number;
};

type ValidationErrorResponse = {
    message?: string;
    errors?: Record<string, string[]>;
};

const props = defineProps<{
    defaultRate: number;
    currencies: Currency[];
    example: {
        converted: number;
        discounted: number;
    };
}>();

defineOptions({
    layout: PublicLayout,
});

const amount = ref<number | string>(100);
const from = ref<CurrencyCode>('EUR');
const to = ref<CurrencyCode>('USD');
const rate = ref<number | string>(props.defaultRate);

const converting = ref(false);
const conversion = ref<ConversionResult | null>(null);
const conversionError = ref('');

const price = ref<number | string>(250);
const percentage = ref<number | string>(20);

const discounting = ref(false);
const discountResult = ref<DiscountResult | null>(null);
const discountError = ref('');

const currencyByCode = computed(
    () =>
        Object.fromEntries(
            props.currencies.map((currency) => [currency.code, currency]),
        ) as Record<CurrencyCode, Currency>,
);

function format(value: number, currency: CurrencyCode): string {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency,
    }).format(value);
}

function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return cookie
        ? decodeURIComponent(cookie.split('=').slice(1).join('='))
        : '';
}

async function postJson<T>(
    url: string,
    payload: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(payload),
    });

    const data: unknown = await response.json();

    if (!response.ok) {
        const errorResponse = data as ValidationErrorResponse;
        const firstError = errorResponse.errors
            ? Object.values(errorResponse.errors).flat()[0]
            : errorResponse.message;

        throw new Error(
            firstError ??
                'No se pudo completar la operación. Revisa los datos.',
        );
    }

    return data as T;
}

async function convert(): Promise<void> {
    conversionError.value = '';
    converting.value = true;

    try {
        conversion.value = await postJson<ConversionResult>('/prices/convert', {
            amount: Number(amount.value),
            from: from.value,
            to: to.value,
            rate: Number(rate.value),
        });
    } catch (error) {
        conversion.value = null;
        conversionError.value =
            error instanceof Error
                ? error.message
                : 'No se pudo completar la conversión.';
    } finally {
        converting.value = false;
    }
}

async function applyDiscount(): Promise<void> {
    discountError.value = '';
    discounting.value = true;

    try {
        discountResult.value = await postJson<DiscountResult>(
            '/prices/discount',
            {
                price: Number(price.value),
                percentage: Number(percentage.value),
            },
        );
    } catch (error) {
        discountResult.value = null;
        discountError.value =
            error instanceof Error
                ? error.message
                : 'No se pudo calcular el descuento.';
    } finally {
        discounting.value = false;
    }
}

function swapCurrencies(): void {
    [from.value, to.value] = [to.value, from.value];
    conversion.value = null;
    conversionError.value = '';
}

onMounted(() => {
    void convert();
    void applyDiscount();
});
</script>

<template>
    <Head title="Calculadora de precios" />

    <section class="mb-10 flex flex-col gap-4">
        <Badge variant="outline" class="w-fit gap-1.5 py-1">
            <Sparkles class="size-3.5" />
            Herramienta de precios
        </Badge>

        <div class="space-y-3">
            <h1
                class="text-3xl font-semibold tracking-tight sm:text-4xl lg:text-5xl"
            >
                Calculadora de precios
            </h1>
            <p class="max-w-2xl text-base text-muted-foreground sm:text-lg">
                Convierte importes entre euros y dólares y calcula el precio
                final de un producto después de aplicar un descuento. Todos los
                cálculos se validan con tests unitarios.
            </p>
        </div>

        <div class="flex flex-wrap gap-2 text-sm">
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 font-medium text-emerald-700 dark:text-emerald-300"
            >
                <CheckCircle2 class="size-4" />
                21 tests unitarios superados
            </span>
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-sky-500/10 px-3 py-1 font-medium text-sky-700 dark:text-sky-300"
            >
                <FlaskConical class="size-4" />
                PriceControllerTest
            </span>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <Card>
            <CardHeader>
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <ArrowRightLeft class="size-5" />
                    </div>
                    <div class="space-y-1">
                        <CardTitle>Conversión de monedas</CardTitle>
                        <CardDescription>
                            Euros (€) y dólares estadounidenses ($)
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <form class="space-y-5" @submit.prevent="convert">
                    <div class="grid gap-2">
                        <Label for="amount">Importe</Label>
                        <Input
                            id="amount"
                            v-model="amount"
                            type="number"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            placeholder="100.00"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-2">
                        <div class="grid gap-2">
                            <Label for="from">Desde</Label>
                            <Select v-model="from">
                                <SelectTrigger id="from" class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="currency in currencies"
                                        :key="currency.code"
                                        :value="currency.code"
                                    >
                                        {{ currency.name }} ({{
                                            currency.symbol
                                        }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="mb-0.5"
                            aria-label="Intercambiar monedas"
                            @click="swapCurrencies"
                        >
                            <ArrowRightLeft class="size-4" />
                        </Button>

                        <div class="grid gap-2">
                            <Label for="to">Hasta</Label>
                            <Select v-model="to">
                                <SelectTrigger id="to" class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="currency in currencies"
                                        :key="currency.code"
                                        :value="currency.code"
                                    >
                                        {{ currency.name }} ({{
                                            currency.symbol
                                        }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="rate">Tasa de cambio (1 EUR = ? USD)</Label>
                        <Input
                            id="rate"
                            v-model="rate"
                            type="number"
                            min="0.0001"
                            step="0.0001"
                            inputmode="decimal"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            Valor por defecto: 1 € =
                            {{ defaultRate.toFixed(2) }} $. Puedes usar tu
                            propia tasa.
                        </p>
                    </div>

                    <Button type="submit" class="w-full" :disabled="converting">
                        <RefreshCw
                            class="size-4"
                            :class="{ 'animate-spin': converting }"
                        />
                        Convertir
                    </Button>

                    <p
                        v-if="conversionError"
                        class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                    >
                        {{ conversionError }}
                    </p>

                    <div
                        v-if="conversion"
                        class="rounded-lg border bg-muted/40 p-4"
                    >
                        <p class="text-sm text-muted-foreground">
                            {{ format(conversion.amount, conversion.from) }}
                            equivale a
                        </p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight">
                            {{ format(conversion.converted, conversion.to) }}
                        </p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            Tasa aplicada: 1 EUR =
                            {{ conversion.rate }} USD ·
                            {{ currencyByCode[conversion.from].name }} →
                            {{ currencyByCode[conversion.to].name }}
                        </p>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400"
                    >
                        <BadgePercent class="size-5" />
                    </div>
                    <div class="space-y-1">
                        <CardTitle>Descuento de precios</CardTitle>
                        <CardDescription>
                            Calcula el ahorro y el precio final
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <form class="space-y-5" @submit.prevent="applyDiscount">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="price">Precio original (€)</Label>
                            <Input
                                id="price"
                                v-model="price"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="250.00"
                                required
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="percentage">Descuento (%)</Label>
                            <Input
                                id="percentage"
                                v-model="percentage"
                                type="number"
                                min="0"
                                max="100"
                                step="0.5"
                                inputmode="decimal"
                                placeholder="20"
                                required
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <input
                            v-model="percentage"
                            type="range"
                            min="0"
                            max="100"
                            step="1"
                            class="h-2 w-full cursor-pointer appearance-none rounded-full bg-muted accent-primary"
                            aria-label="Porcentaje de descuento"
                        />
                        <div
                            class="flex justify-between text-xs text-muted-foreground"
                        >
                            <span>0 %</span>
                            <span>50 %</span>
                            <span>100 %</span>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="discounting"
                    >
                        <RefreshCw
                            class="size-4"
                            :class="{ 'animate-spin': discounting }"
                        />
                        Calcular descuento
                    </Button>

                    <p
                        v-if="discountError"
                        class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                    >
                        {{ discountError }}
                    </p>

                    <div v-if="discountResult" class="space-y-3">
                        <div class="rounded-lg border bg-muted/40 p-4">
                            <p class="text-sm text-muted-foreground">
                                Precio final con
                                {{ discountResult.percentage }} % de descuento
                            </p>
                            <p
                                class="mt-1 text-3xl font-semibold tracking-tight"
                            >
                                {{ format(discountResult.final_price, 'EUR') }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Ahorras
                                {{ format(discountResult.discount, 'EUR') }}
                                sobre
                                {{ format(discountResult.price, 'EUR') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-lg border p-3">
                                <p class="text-muted-foreground">Ahorro</p>
                                <p
                                    class="mt-1 font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    -{{
                                        format(discountResult.discount, 'EUR')
                                    }}
                                </p>
                            </div>
                            <div class="rounded-lg border p-3">
                                <p class="text-muted-foreground">
                                    Precio original
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ format(discountResult.price, 'EUR') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>

    <Card class="mt-6">
        <CardHeader>
            <div class="flex items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400"
                >
                    <FlaskConical class="size-5" />
                </div>
                <div class="space-y-1">
                    <CardTitle>Pruebas unitarias incluidas</CardTitle>
                    <CardDescription>
                        El archivo tests/Unit/PriceControllerTest.php comprueba
                        ambas funciones del controlador.
                    </CardDescription>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <ul class="grid gap-3 text-sm sm:grid-cols-3">
                <li class="rounded-lg border p-3">
                    <p class="font-medium">Conversión EUR ↔ USD</p>
                    <p class="mt-1 text-muted-foreground">
                        Tasa por defecto y personalizada, conversión inversa,
                        redondeo y monedas no soportadas.
                    </p>
                </li>
                <li class="rounded-lg border p-3">
                    <p class="font-medium">Descuentos</p>
                    <p class="mt-1 text-muted-foreground">
                        Del 0 % al 100 %, precios con decimales, redondeo y
                        porcentajes fuera de rango.
                    </p>
                </li>
                <li class="rounded-lg border p-3">
                    <p class="font-medium">Validaciones</p>
                    <p class="mt-1 text-muted-foreground">
                        Importes y precios negativos, tasas inválidas y datos
                        incorrectos.
                    </p>
                </li>
            </ul>

            <div
                class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border bg-muted/40 px-4 py-3 text-sm"
            >
                <span class="inline-flex items-center gap-1.5 font-medium">
                    <CheckCircle2
                        class="size-4 text-emerald-600 dark:text-emerald-400"
                    />
                    Ejemplo calculado en el servidor:
                </span>
                <span class="text-muted-foreground">
                    100 € = {{ format(example.converted, 'USD') }}
                </span>
                <span class="text-muted-foreground">
                    100 € con 20 % de descuento =
                    {{ format(example.discounted, 'EUR') }}
                </span>
            </div>
        </CardContent>
    </Card>
</template>
