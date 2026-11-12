const globalSearchMethods = {
  buscarProductoGlobal(){
    if(this.buscarGlobal.length < 4){
      this.resultadosGlobal = [];
      return;
    }
    axios.get(`${this.apphost}/pet/product/buscar_global`,{
      params:{ q:this.buscarGlobal }
    })
    .then(r=>{
      this.resultadosGlobal = r.data;
    });
  },

  seleccionarProductoGlobal(p){
    this.nuevo.name = p.nombre;
    this.nuevo.price_ref = Number(p.precio);

    const cat = this.categorias.find(c => c.category_id == p.categoria_global_id);
    if(cat){
      this.nuevo.categorias = [cat];
    }

    this.buscarGlobal = p.nombre;
    this.resultadosGlobal = [];
  }
};