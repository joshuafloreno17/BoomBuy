const imageInput = document.getElementById('image');
const previewBox = document.getElementById('image-preview');
const previewImage = document.getElementById('preview-image');

imageInput.addEventListener('change', function () {

    const file = this.files[0];

    if (!file) {

        previewBox.style.display = 'none';
        previewImage.src = '';

        return;
    }

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!allowedTypes.includes(file.type)) {

        alert(
            'Please select a JPG, JPEG, PNG, or WEBP image.'
        );

        this.value = '';

        previewBox.style.display = 'none';
        previewImage.src = '';

        return;
    }

    if (file.size > 5 * 1024 * 1024) {

        alert(
            'Product image must not be larger than 5MB.'
        );

        this.value = '';

        previewBox.style.display = 'none';
        previewImage.src = '';

        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {

        previewImage.src = event.target.result;

        previewBox.style.display = 'block';

    };

    reader.readAsDataURL(file);

});
