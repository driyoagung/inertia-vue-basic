<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    posts: Array
});

const isModalOpen = ref(false);
const isEdit = ref(false);
const currentPostId = ref(null);

const form = useForm({
    title: '',
    content: ''
});

const openModal = (post = null) => {
    form.clearErrors();
    if (post) {
        isEdit.value = true;
        currentPostId.value = post.id;
        form.title = post.title;
        form.content = post.content;
    } else {
        isEdit.value = false;
        currentPostId.value = null;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const showToast = (message) => {
    if (window.Toastify) {
        window.Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: "top", // `top` or `bottom`
            position: "right", // `left`, `center` or `right`
            backgroundColor: "#4f46e5", // Indigo-600
        }).showToast();
    }
};

const submit = () => {
    if (isEdit.value) {
        form.put(`/posts/${currentPostId.value}`, {
            onSuccess: () => {
                closeModal();
                showToast("Post updated successfully!");
            }
        });
    } else {
        form.post('/posts', {
            onSuccess: () => {
                closeModal();
                showToast("Post created successfully!");
            }
        });
    }
};

const deletePost = (post) => {
    if (confirm('Are you sure you want to delete this post?')) {
        router.delete(`/posts/${post.id}`, {
            onSuccess: () => {
                showToast("Post deleted successfully!");
            }
        });
    }
};
</script>

<template>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Posts Management</h2>
            <button @click="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                Add New Post
            </button>
        </div>

        <!-- Posts Table -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="post in posts" :key="post.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ post.id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ post.title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ post.content }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button @click="openModal(post)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                            <button @click="deletePost(post)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="posts.length === 0">
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No posts found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none">
            <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>
            <div class="relative w-auto max-w-lg mx-auto my-6 z-50">
                <div class="relative flex flex-col w-full bg-white border-0 rounded-lg shadow-lg outline-none focus:outline-none min-w-[400px]">
                    <div class="flex items-start justify-between p-5 border-b border-solid rounded-t border-gray-300">
                        <h3 class="text-xl font-semibold text-gray-900">
                            {{ isEdit ? 'Edit Postingan' : 'Create Post' }}
                        </h3>
                        <button class="p-1 ml-auto bg-transparent border-0 text-black float-right text-3xl leading-none font-semibold outline-none focus:outline-none" @click="closeModal">
                            <span class="text-gray-500 hover:text-gray-800">×</span>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submit">
                        <div class="relative p-6 flex-auto">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">Title</label>
                                <input v-model="form.title" type="text" id="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" :class="{ 'border-red-500': form.errors.title }" placeholder="Enter title">
                                <p v-if="form.errors.title" class="text-red-500 text-xs italic mt-1">{{ form.errors.title }}</p>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="content">Content</label>
                                <textarea v-model="form.content" id="content" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" :class="{ 'border-red-500': form.errors.content }" placeholder="Enter content"></textarea>
                                <p v-if="form.errors.content" class="text-red-500 text-xs italic mt-1">{{ form.errors.content }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end p-6 border-t border-solid rounded-b border-gray-300">
                            <button type="button" @click="closeModal" class="text-red-500 background-transparent font-bold uppercase px-6 py-2 text-sm outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150 hover:bg-red-50 rounded">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 font-bold uppercase text-sm px-6 py-3 rounded shadow hover:shadow-lg outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150 disabled:opacity-50">
                                {{ form.processing ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
