<template>
    <PLayout sectioned v-if="!isLoading">
        <PBanner :status="status" v-if="message != ''">{{ message }}</PBanner>
        <PCard sectioned class="themes-wrapper">
            <PFormLayout>
                <PCheckbox
                    id="select_all"
                    :checked="isCheckedAll"
                    @change="handleChange"
                    label="Select All"
                    :style="{'padding-left': '.8rem'}" />
                
                <POptionList
                    :options="themes"
                    :selected="selected"
                    :allowMultiple="true"
                    @change="handleSelect">
                </POptionList>
            </PFormLayout>
            <PButton 
                primary 
                v-bind:disabled="selected == initialSelected"
                v-bind:loading="isSaving"
                slot="footer"
                @click="save">
                Save
            </PButton>
        </PCard>
    </PLayout>
</template>

<script>
export default {
    name: 'Themes',
    data: function() {
        return {
            status: false,
            message: '',
            isLoading: false,
            isSaving: false,
            isCheckedAll: false,
            themes: [],
            selected: [],
            initialSelected: [],
            allThemes: []
        }
    },
    created: function() {
        this.isLoading = true;
        this.axios
            .get('/api/themes')
            .then((res) => {
                this.themes = res.data.themes;
                this.initialSelected = res.data.installed;
                this.selected = res.data.installed;
                this.allThemes = res.data.theme_ids;
                this.isLoading = false;
            });
    },
    methods: {
        handleChange: function() {
            this.isCheckedAll = !this.isCheckedAll;
            if(this.isCheckedAll) {
                this.selected = this.allThemes;
            } else {
                this.selected = [];
            }
        },
        handleSelect: function(value) {
            this.selected = value;
        },
        save: function() {
            this.isSaving = true;
            this.axios
                .post('/api/themes', {'selected': this.selected})
                .then((res) => {
                    this.isSaving = false;
                    this.status = res.data.status;
                    this.message = res.data.message;
                    this.initialSelected = this.selected;
                });
        }
    }
}
</script>
<style lang="scss" src="./index.scss" scoped></style>
