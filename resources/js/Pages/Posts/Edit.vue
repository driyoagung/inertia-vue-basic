<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    post: Object,
});

const form = useForm({
    title: props.post.title,
    content: props.post.content,
    image: null,
    _method: 'put', // Spoofing for Laravel form method to handle files via POST
});

// Start preview with existing image if available
const imagePreview = ref(props.post.image_url);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        // Create live preview for new image
        imagePreview.value = URL.createObjectURL(file);
    } else {
        form.image = null;
        // Revert to existing image if user cancels file selection
        imagePreview.value = props.post.image_url;
    }
};

const submit = () => {
    // We send as POST to support file uploads, Laravel will treat it as PUT via _method spoofing
    form.post(`/posts/${props.post.id}`);
};
</script>

<template>
    <div class="container mx-auto p-4 max-w-2xl">
        <div class="mb-6">
            <Link href="/posts" class="text-blue-600 hover:text-blue-800 flex items-center">
                &larr; Back to Posts
            </Link>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Edit Post</h1>

        <form @submit.prevent="submit" class="bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Title
                </label>
                <input v-model="form.title" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" id="title" type="text" placeholder="Post Title" required>
                <div v-if="form.errors.title" class="text-red-500 text-xs italic mt-2">{{ form.errors.title }}</div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                    Content
                </label>
                <textarea v-model="form.content" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all h-32 resize-none" id="content" placeholder="Post content..." required></textarea>
                <div v-if="form.errors.content" class="text-red-500 text-xs italic mt-2">{{ form.errors.content }}</div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                    Update Image (Optional)
                </label>
                <input @change="handleImageChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer border border-gray-200 rounded-lg p-2" id="image" type="file" accept="image/*">
                <div v-if="form.errors.image" class="text-red-500 text-xs italic mt-2">{{ form.errors.image }}</div>
            </div>
            
            <!-- Image Preview Area -->
            <div v-if="imagePreview" class="mb-8 p-4 border rounded-lg bg-gray-50 flex flex-col items-center">
                <p class="text-sm text-gray-500 mb-2 font-medium">Image Preview</p>
                <img :src="imagePreview" class="max-h-64 object-contain rounded shadow-sm" alt="Preview" />
            </div>

            <div class="flex items-center justify-end">
                <button :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors shadow flex items-center" type="submit">
                    <span v-if="form.processing" class="mr-2">Updating...</span>
                    Update Post
                </button>
            </div>
        </form>
    </div>
</template>
