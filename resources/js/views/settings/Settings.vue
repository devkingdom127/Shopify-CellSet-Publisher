<template>
    <PLayout sectioned v-if="!isLoading">
        <PBanner v-bind:status="status" v-if="message != ''">{{ message }}</PBanner>
        <PCard sectioned class="settings-wrapper">
            <PFormLayout>
                <PTextField id="site_id" label="Site ID" v-model="siteId" v-bind:error="errorMessage" />
            </PFormLayout>
            <PButton 
                primary 
                v-bind:disabled="siteId == '' || siteId == initialSiteId"
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
    name: 'Settings',
    data: function() {
        return {
            siteId: "",
            initialSiteId: "",
            errorMessage: "",
            isLoading: false,
            isSaving: false,
            status: false,
            message: ''
        };
    },
    watch: {
        siteId: function(val) {
            if(val == "") {
                this.errorMessage = "This field is required.";
            } else {
                this.errorMessage = "";
            }
        }
    },
    created: function() {
        this.isLoading = true;
        this.axios
            .get('/api/settings')
            .then((res) => {
                this.siteId = res.data.data;
                this.initialSiteId = res.data.data;
                this.isLoading = false;
            });
    },
    methods: {
        save: function() {
            this.isSaving = true;
            this.axios
                .post('/api/settings', {'site_id': this.siteId})
                .then((res) => {
                    this.isSaving = false;
                    this.status = res.data.status;
                    this.message = res.data.message;
                    this.initialSiteId = this.siteId;
                });
        }
    }
}
</script>
<style lang="scss" src="./index.scss" scoped></style>
