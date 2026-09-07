<script setup>
import { ref, watch, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    provinces: {
        type: Array,
        required: true
    }
});

const form = useForm({
    name: '',
    province_id: '',
    city_id: '',
    district_id: '',
});

const flash = computed(() => usePage().props.flash);

const cities = ref([]);
const districts = ref([]);

const loadingCities = ref(false);
const loadingDistricts = ref(false);

// Watch for changes in Province
watch(() => form.province_id, async (newVal) => {
    form.city_id = '';
    form.district_id = '';
    cities.value = [];
    districts.value = [];

    if (newVal) {
        loadingCities.value = true;
        try {
            const response = await fetch(`/api/cities?province_id=${newVal}`);
            cities.value = await response.json();
        } catch (error) {
            console.error('Error fetching cities:', error);
        } finally {
            loadingCities.value = false;
        }
    }
});

// Watch for changes in City
watch(() => form.city_id, async (newVal) => {
    form.district_id = '';
    districts.value = [];

    if (newVal) {
        loadingDistricts.value = true;
        try {
            const response = await fetch(`/api/districts?city_id=${newVal}`);
            districts.value = await response.json();
        } catch (error) {
            console.error('Error fetching districts:', error);
        } finally {
            loadingDistricts.value = false;
        }
    }
});

const submit = () => {
    form.post('/study-cases/dependent-dropdown', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            cities.value = [];
            districts.value = [];
        }
    });
};
</script>

<template>
    <Head title="Dependent Dropdown" />

    <div class="min-h-screen bg-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Dependent Dropdown
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Provinsi &rarr; Kota &rarr; Kecamatan
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <!-- Flash Success Message -->
                <div v-if="flash?.success" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <p class="text-sm font-medium text-green-800">{{ flash.success }}</p>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <!-- Nama Employee -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Employee
                        </label>
                        <div class="mt-1">
                            <input
                                id="name"
                                type="text"
                                v-model="form.name"
                                placeholder="Masukkan nama employee"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-700">
                            Provinsi
                        </label>
                        <div class="mt-1">
                            <select 
                                id="province" 
                                v-model="form.province_id"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option value="">-- Pilih Provinsi --</option>
                                <option v-for="province in provinces" :key="province.id" :value="province.id">
                                    {{ province.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 flex items-center justify-between">
                            Kota/Kabupaten
                            <span v-if="loadingCities" class="text-xs text-indigo-500">Loading...</span>
                        </label>
                        <div class="mt-1">
                            <select 
                                id="city" 
                                v-model="form.city_id"
                                :disabled="!form.province_id || loadingCities"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-500"
                            >
                                <option value="">-- Pilih Kota --</option>
                                <option v-for="city in cities" :key="city.id" :value="city.id">
                                    {{ city.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="district" class="block text-sm font-medium text-gray-700 flex items-center justify-between">
                            Kecamatan
                            <span v-if="loadingDistricts" class="text-xs text-indigo-500">Loading...</span>
                        </label>
                        <div class="mt-1">
                            <select 
                                id="district" 
                                v-model="form.district_id"
                                :disabled="!form.city_id || loadingDistricts"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-500"
                            >
                                <option value="">-- Pilih Kecamatan --</option>
                                <option v-for="district in districts" :key="district.id" :value="district.id">
                                    {{ district.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="p-4 bg-gray-50 border rounded-md text-sm text-gray-700 space-y-1">
                            <p><strong>Selected Value:</strong></p>
                            <p>Province ID: {{ form.province_id || '-' }}</p>
                            <p>City ID: {{ form.city_id || '-' }}</p>
                            <p>District ID: {{ form.district_id || '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
