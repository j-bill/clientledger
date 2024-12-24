<!-- 

To use in the parent component, insert:

<aiDateTimePicker @selection="handleDateTimeSelection" />

Import `aiDateTimePicker` from the respective file:

import aiDateTimePicker from '../components/ai-date-time-picker.vue';

Add `aiDateTimePicker` to the "components" section:

components: {
    aiDateTimePicker,
}

-------

Available Props:

initialDate: Default date, useful for pre-filling from the database.
initialTime: Default time, useful for pre-filling from the database.
pickerBgColor: Background color of the date/time picker.
pickerAccentColor: Accent color for highlighting.
prependIcon: Icon in front of the text field (leave as `null` for no icon).
minDate: Minimum selectable date.
maxDate: Maximum selectable date.
title: Title for the text field (acts as label).
showAdjacentMonths: Boolean to show adjacent months in the date picker, defaults to true.
variant: Text field variant, e.g., 'outlined' or 'filled', defaults to 'outlined'.
required: If the field is required.
disabled: If the field is disabled.

-->

<template>
    <v-menu location="" ref="menu" v-model="menu" :nudge-right="40" :close-on-content-click="false" transition="scale-transition">
        <template v-slot:activator="{ props }">
            <v-text-field
                v-model="formattedDateTime"
                :disabled="disabled"
                :label="title"
                :prepend-icon="prependIcon"
                readonly
                v-bind="props"
                :variant="variant"
                :rules="rules" />
        </template>

        <v-card>
            <v-tabs v-model="activeTab">
                <v-tab value="one">Date</v-tab>
                <v-tab value="two">Time</v-tab>
            </v-tabs>

            <v-tabs-window v-model="activeTab">
                <!-- Date Picker -->
                <v-tabs-window-item value="one">
                    <v-date-picker
                        v-model="date"
                        @update:modelValue="updateDate"
                        :bg-color="pickerBgColor"
                        :color="pickerAccentColor"
                        :min="minDate"
                        :max="maxDate"
                        :title="title"
                        locale="de"
                        first-day-of-week="1"
                        :show-adjacent-months="showAdjacentMonths"></v-date-picker>
                </v-tabs-window-item>

                <v-tabs-window-item value="two">
                    <v-time-picker v-model="time" @update:modelValue="updateTime" full-width format="24hr" :color="pickerAccentColor" locale="de"></v-time-picker>
                </v-tabs-window-item>
            </v-tabs-window>
        </v-card>
    </v-menu>
</template>

<script>
export default {
    props: {
        initialDate: {
            type: String,
            default: '',
        },
        initialTime: {
            type: String,
            default: '',
        },
        pickerBgColor: {
            type: String,
        },
        pickerAccentColor: {
            type: String,
        },
        prependIcon: {
            type: String,
        },
        minDate: {
            type: String,
        },
        maxDate: {
            type: String,
        },
        title: {
            type: String,
            default: 'Date & Time',
        },
        showAdjacentMonths: {
            type: Boolean,
            default: true,
        },
        variant: {
            type: String,
            default: 'outlined',
        },
        required: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['selection'],
    data() {
        return {
            menu: false,
            date: null,
            time: null,
            activeTab: "one",
        };
    },
    mounted() {
        this.updateDateTimeFromProps();
    },
    updated() {
        this.updateDateTimeFromProps();
    },
    computed: {
        formattedDateTime() {
            if (!this.date) return '';
            const date = new Date(this.date);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const time = this.time ? ` ${this.time}` : '';
            return `${day}.${month}.${year}${time}`;
        },
        rules() {
            const rules = [];
            if (this.required) rules.push((value) => !!value || 'Pflichtfeld.');
            return rules;
        },
    },
    methods: {
        updateDateTimeFromProps() {
            if (this.initialDate) this.date = new Date(this.initialDate);
            if (this.initialTime) this.time = this.initialTime;
        },
        updateDate(val) {
            this.date = val;
            if (this.activeTab === 'one') this.activeTab = 'two';
        },
        updateTime(val) {
            this.time = val;

            if (this.time && this.date) {
                this.saveDateTime();
                this.menu = false;
            }
        },
        saveDateTime() {
            if (this.date && this.time) {
                const date = new Date(this.date);
                const timeParts = this.time.split(':');
                date.setHours(timeParts[0], timeParts[1]);
                this.$emit('selection', date);
            }
        },
    },
};
</script>