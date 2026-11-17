Vue.component('v-select', VueSelect.VueSelect);

const appTema = new Vue({
    el: '#appTema',
    data: temaState,
    methods: {
        ...temaMethods,
        ...temaxnegMethods
    },
    mounted() {
        this.listarTemas();
    }
});