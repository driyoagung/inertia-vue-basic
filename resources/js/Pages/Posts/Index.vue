<script setup>
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

defineProps({
    posts: Array,
});

const deletePost = (id) => {
    if (confirm('Are you sure you want to delete this post?')) {
        router.delete(`/posts/${id}`);
    }
};
</script>

<template>
    <div class="container mx-auto p-4 max-w-4xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Posts</h1>
            <Link href="/posts/create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                Create Post
            </Link>
        </div>

        <div v-if="$page.props.flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 shadow-sm" role="alert">
            <span class="block sm:inline">{{ $page.props.flash.success }}</span>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-4 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Image
                        </th>
                        <th class="px-5 py-4 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Title
                        </th>
                        <th class="px-5 py-4 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="post in posts" :key="post.id" class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-5 py-5 border-b border-gray-200 text-sm">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-20 h-20">
                                    <img v-if="post.image_url" class="w-full h-full object-cover rounded-lg shadow-sm" :src="post.image_url" alt="Post image" />
                                    <div v-else class="w-full h-full bg-gray-200 rounded-lg shadow-sm flex items-center justify-center text-gray-500 text-xs text-center p-2">
                                        No Image
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 text-sm">
                            <p class="text-gray-900 font-medium whitespace-no-wrap">{{ post.title }}</p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 text-sm">
                            <div class="flex space-x-3">
                                <Link :href="`/posts/${post.id}/edit`" class="text-indigo-600 hover:text-indigo-900 font-medium transition-colors">Edit</Link>
                                <button @click="deletePost(post.id)" class="text-red-600 hover:text-red-900 font-medium transition-colors">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="posts.length === 0">
                        <td colspan="3" class="px-5 py-8 border-b border-gray-200 bg-white text-sm text-center text-gray-500">
                            No posts found. Start by creating one!
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
