<!-- 

To use in the parent component:

<aiDatePicker @selection="handleDateSelection" /> 

Import `aiDatePicker` from the respective file:

import aiDatePicker from '../components/ai-date-picker.vue'; 

Add `aiDatePicker` to the "components" section:

components: {
    aiDatePicker,
}

-------

Available Props:

initialDate: Default date, for example, a date from the database.
pickerBgColor: Background color of the date picker.
pickerAccentColor: Accent color of the date picker.
prependIcon: Icon in front of the text field, if "null" then no icon is displayed.
minDate: Minimum selectable date.
maxDate: Maximum selectable date.
title: Title for the text field (Label).
showAdjacentMonths: Boolean to show adjacent months in the date picker, defaults to true.
variant: Text field variant, e.g., 'outlined' or 'filled', defaults to 'outlined'.
required: If the field is required.
disabled: If the field is disabled.
hideDetails: If the text field details should be hidden.

-->

<template>
    <v-menu location="" ref="menu" v-model="menu" :nudge-right="40" :close-on-content-click="false" transition="scale-transition">
        <template v-slot:activator="{ props }">
            <v-text-field
                v-model="formattedDate"
                :disabled="disabled"
                :label="title"
                :prepend-icon="prependIcon"
                readonly
                v-bind="props"
                :variant="variant"
                :hide-details="hideDetails"
                :rules="rules" />
        </template>
        <v-date-picker
            v-model="date"
            @update:modelValue="closeDatePicker"
            :bg-color="pickerBgColor"
            :color="pickerAccentColor"
            :min="minDate"
            :max="maxDate"
            :title="title"
            locale="de"
            first-day-of-week="1"
            :show-adjacent-months="showAdjacentMonths"></v-date-picker>
    </v-menu>
  </template>
  
  <script>
  export default {
    props: {
        initialDate: {
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
            default: 'Date',
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
        hideDetails: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['selection'],
    data() {
        return {
            menu: false,
            date: null,
        };
    },
    mounted() {
        // Ensure date is set from initialDate on mount
        this.updateDateFromInitial();
    },
    updated() {
        // Capture updates to initialDate when the component is reused
        this.updateDateFromInitial();
    },
    computed: {
        formattedDate() {
            if (!this.date) return '';
            const date = new Date(this.date);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}.${month}.${year}`;
        },
        rules() {
            const rules = [];
            if (this.required) rules.push((value) => !!value || 'Pflichtfeld.');
            return rules;
        },
    },
    methods: {
        updateDateFromInitial() {
            if (this.initialDate) {
                this.date = new Date(this.initialDate);
            }
        },
        closeDatePicker(val) {
            this.$emit('selection', new Date(val).toISOString());
            this.menu = false;
        },
    },
  };
  </script>