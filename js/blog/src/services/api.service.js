class ApiService {
    constructor(url) {
        this.url = url
    }

    async createPost(post) {
        try {
            const request = new Request(this.url + '/posts.json', {
                method: 'post',
                body: JSON.stringify(post)
            })
            const response = await fetch(request)
            return await response.json()
        } catch (error) {
            console.error('Error!')
        }

    }
}

export const apiService = new ApiService('https://js-blog-8f1e6.firebaseio.com/')
