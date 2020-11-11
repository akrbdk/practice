export class TransformService{
    static fbObjectToArray(fbObject){
        return Object.keys(fbObject).map(key => {
            const item = fbObject[key]
            item.id = key
            return item
        })
    }
}
