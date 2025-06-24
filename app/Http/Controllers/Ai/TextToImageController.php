            $prompt = $request->input('prompt');
            $imageStyle = $request->input('imageStyle');
            $aspectRatio = $request->input('aspectRatio', '1:1');
            $enhancePrompt = $request->boolean('enhancePrompt', false);
            $userId = $request->user()?->id;

            // Generate images
            $imageUrls = $this->textToImageService->Imagegenerate(
                $prompt,
                $imageStyle,
                $aspectRatio,
                1, // n is always 1
                'url', // response_format is always url
                $userId,
                $enhancePrompt
            ); 