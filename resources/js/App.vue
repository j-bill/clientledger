<template>
    <v-app>
        <v-main class="d-flex align-center justify-center" style="min-height: 100vh">
            <v-container>
                <v-card class="elevation-2 pa-8" dark>
                    <v-card-title class="display-1 mb-4 font-weight-bold text-center"> ADMIN INTELLIGENCE GmbH </v-card-title>
                    <v-card-subtitle class="mb-1">
                        <div class="subtitle-1 text-center">Boilerplate Project for Laravel 11, Vue 3, and Vuetify 3</div>
                    </v-card-subtitle>

                    <v-card-text class="mb-6">
                        <v-tabs v-model="tab">
                            <v-tab value="one">Setup</v-tab>
                            <v-tab value="two">.env</v-tab>
                            <v-tab value="three">API function test</v-tab>
                            <v-tab value="four">Custom Components</v-tab>
                            <v-tab value="five">Custom Pages</v-tab>
                        </v-tabs>

                        <v-tabs-window v-model="tab">
                            <v-tabs-window-item value="one">
                                <div class="text-h6 mt-6">Project Setup</div>

                                <v-list density="compact">
                                    <v-list-item>Auth with Sanctum</v-list-item>
                                    <v-list-item>Stateful Sanctum Middleware</v-list-item>
                                    <v-list-item>Vue 3 with Vuetify 3</v-list-item>
                                    <v-list-item>Axios Integration</v-list-item>
                                    <v-list-item>MDI Icons</v-list-item>
                                </v-list>

                                <div class="text-h6">Run Commands</div>
                                <div class="code-block">
                                    composer i<br />
                                    php artisan migrate<br />
                                    php artisan db:seed<br />
                                    npm i<br />
                                    npm run dev<br />
                                </div>
                            </v-tabs-window-item>

                            <v-tabs-window-item value="two">
                                <div class="text-h6 mt-6">Environment Config (.env)</div>
                                <div class="code-block">
                                    APP_URL=https://domain.test<br />
                                    SESSION_DOMAIN=domain.test<br />
                                    SANCTUM_STATEFUL_DOMAINS=domain.test<br />
                                    <br />
                                    DB_CONNECTION=mysql<br />
                                    DB_HOST=127.0.0.1<br />
                                    DB_PORT=3306<br />
                                    DB_DATABASE=database<br />
                                    DB_USERNAME=user<br />
                                    DB_PASSWORD=password<br />
                                    <br />
                                    SESSION_DRIVER=cookie<br />
                                </div>

                                <div class="text-h6 mt-6">Permissions</div>
                                <div class="code-block">
                                    chown -R YOURUSER:www-data * && chown -R YOURUSER:www-data .* && chown -R www-data:www-data storage/
                                </div>
                            </v-tabs-window-item>

                            <v-tabs-window-item value="three">
                                <v-form @submit.prevent="login">
                                    <div class="text-h6 mt-6 mb-6">Test Auth to check if API + Sanctum works</div>

                                    <v-text-field label="Email" v-model="email" required variant="outlined" />
                                    <v-text-field label="Password" v-model="password" type="password" required variant="outlined" />

                                    <v-btn type="submit" color="primary" class="mt-0">Login</v-btn>
                                    <v-alert v-if="response" :type="type" class="mt-6">{{ response }}</v-alert>
                                </v-form>
                            </v-tabs-window-item>

                            <v-tabs-window-item value="four">
                                <v-row class="pa-4 mt-6">
                                    <v-col cols="6">
                                        <v-form ref="form">
                                            <aiDatePicker disabled @selection="printDate" required />

                                            <aiDataTimePicker @selection="printDate" />

                                            <v-btn @click="validate()" class="mt-5 ml-n2" color="primary">Validate</v-btn>
                                        </v-form>
                                    </v-col>
                                </v-row>
                            </v-tabs-window-item>
                            <v-tabs-window-item value="five"></v-tabs-window-item>
                        </v-tabs-window>

                        <v-divider class="my-4"></v-divider>

                        <div class=""></div>
                    </v-card-text>

                    <v-divider class="my-6"></v-divider>
                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>

<script>
import axios from 'axios';

import aiDatePicker from '../components/ai-date-picker.vue';
import aiDataTimePicker from '../components/ai-data-time-picker.vue';

export default {
    components: {
        aiDatePicker,
        aiDataTimePicker,
    },
    data() {
        return {
            email: 'admin@admin.de',
            password: 'adminadmin',
            response: '',
            type: 'error',
            tab: null,
        };
    },
    methods: {
        async validate() {
            const { valid } = await this.$refs.form.validate();

            if (valid) {
                alert('Form is valid');
            } else {
                alert('Form is invalid');
            }
        },
        async login() {
            this.response = '';
            this.type = '';

            try {
                const response = await axios.post('/api/login', {
                    email: this.email,
                    password: this.password,
                });
                this.type = 'success';
                this.response = response.data.message;
            } catch (error) {
                this.type = 'error';
                this.response = error.response?.data?.message || 'An error occurred';
            }
        },
        printDate(date) {
            alert(date);
        },
    },
};
</script>

<style scoped>
body {
    font-family: Arial, sans-serif;
}

.code-block {
    background-color: #333;
    color: #e0e0e0;
    padding: 16px;
    font-family: monospace;
    border-radius: 8px;
    white-space: pre-wrap;
    /* Preserves formatting and line breaks */
    overflow: auto;
    margin: 8px 0;
    line-height: 1.5;
    /* Improves readability */
}
</style>
