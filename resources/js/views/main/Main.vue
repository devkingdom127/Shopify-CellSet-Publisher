<template>
    <PPage>
        <PTabs :tabs="tabs"
            :selected="selected"
            @select="handleMenuClick">
            <keep-alive>
                <Settings v-if="selected === 0" />
                <Themes v-if="selected === 1" />
            </keep-alive>
        </PTabs>
    </PPage>
</template>
<script>
    import Settings from '../settings/Settings';
    import Themes from '../themes/Themes';
    export default {
        name: 'Main',
        data: function() {
            return {
                selected: 0,
                tabs: [
                    {
                        id: "0",
                        content: "Settings",
                    },
                    {
                        id: "1",
                        content: "Themes",
                    },
                ]
            }
        },
        methods: {
            handleMenuClick: function(selectedIndex) {
                if(this.selected != selectedIndex) {
                    this.selected = selectedIndex;
                }
            }
        },
        components: {
            Settings,
            Themes
        }
    }
</script>