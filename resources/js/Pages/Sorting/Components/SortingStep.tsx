import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface Folder {
    id: string;
    name: string;
}

interface Image {
    id: string;
    name: string;
    mimeType: string;
    size: number;
    thumbnailLink?: string;
    webContentLink?: string;
    width?: number;
    height?: number;
}

interface SortingSession {
    id: number;
    source_folder_id: string;
    source_folder_name: string;
    destination_folders: Folder[];
    images: Image[];
    total_images: number;
    sorted_images: number;
    remaining_images: number;
    current_image_index: number;
    status: string;
}

interface Props {
    session: SortingSession;
    onSessionUpdate: (session: SortingSession) => void;
    onReset: () => void;
}

export default function SortingStep({ session, onSessionUpdate, onReset }: Props) {
    const [loading, setLoading] = useState(false);
    const [imageLoading, setImageLoading] = useState(true);
    const currentImage = session.images[session.current_image_index];

    const handleSortImage = async (folderId: string) => {
        if (loading || !currentImage) return;

        setLoading(true);
        try {
            const response = await axios.post('/sorting/session/sort', {
                session_id: session.id,
                image_index: session.current_image_index,
                destination_folder_id: folderId,
            });

            onSessionUpdate(response.data.session);

            // Check if completed
            if (response.data.session.status === 'completed') {
                alert('Sorting completed! All images have been sorted.');
                onReset();
            }
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to sort image');
        } finally {
            setLoading(false);
            setImageLoading(true);
        }
    };

    const handleSkip = async (direction: 'next' | 'previous') => {
        if (loading) return;

        setLoading(true);
        setImageLoading(true);
        try {
            const response = await axios.post('/sorting/session/skip', {
                session_id: session.id,
                direction,
            });

            onSessionUpdate(response.data.session);
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to navigate');
        } finally {
            setLoading(false);
        }
    };

    // Keyboard shortcuts
    const handleKeyPress = useCallback(
        (event: KeyboardEvent) => {
            if (loading || !session.destination_folders) return;

            // Number keys 1-9
            const key = event.key;
            if (key >= '1' && key <= '9') {
                const index = parseInt(key) - 1;
                if (index < session.destination_folders.length) {
                    const folder = session.destination_folders[index];
                    handleSortImage(folder.id);
                }
            }

            // Arrow keys
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                handleSkip('previous');
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                handleSkip('next');
            }
        },
        [loading, session.destination_folders, session.current_image_index]
    );

    useEffect(() => {
        window.addEventListener('keydown', handleKeyPress);
        return () => {
            window.removeEventListener('keydown', handleKeyPress);
        };
    }, [handleKeyPress]);

    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    };

    const getImageUrl = (image: Image): string => {
        // Use webContentLink or construct Google Drive preview URL
        if (image.webContentLink) {
            return image.webContentLink;
        }
        return `https://drive.google.com/uc?id=${image.id}&export=view`;
    };

    if (session.status === 'completed') {
        return (
            <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div className="px-8 py-16 text-center">
                    <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg className="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 className="text-3xl font-bold text-gray-900 mb-3">
                        All Done!
                    </h2>
                    <p className="text-lg text-gray-600 mb-8">
                        All {session.total_images} images have been sorted successfully.
                    </p>
                    <PrimaryButton onClick={onReset}>
                        Start New Session
                    </PrimaryButton>
                </div>
            </div>
        );
    }

    if (!currentImage) {
        return (
            <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div className="px-8 py-16 text-center">
                    <p className="text-gray-600">No images to display</p>
                    <PrimaryButton onClick={onReset} className="mt-4">
                        Start Over
                    </PrimaryButton>
                </div>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {/* Main Preview Area */}
            <div className="lg:col-span-9">
                <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-4 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="flex items-center space-x-2 mb-1">
                                    <div className="w-7 h-7 bg-white bg-opacity-30 rounded-full flex items-center justify-center text-xs font-bold">
                                        3
                                    </div>
                                    <h2 className="text-xl font-bold">Sorting Photos</h2>
                                </div>
                                <p className="text-sm text-blue-100">{session.source_folder_name}</p>
                            </div>
                            <button
                                onClick={onReset}
                                className="text-white hover:text-blue-100 transition-colors"
                                title="Cancel and start over"
                            >
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Image Preview */}
                    <div className="relative bg-gray-900 aspect-video flex items-center justify-center">
                        {imageLoading && (
                            <div className="absolute inset-0 flex items-center justify-center bg-gray-900">
                                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
                            </div>
                        )}
                        <img
                            src={getImageUrl(currentImage)}
                            alt={currentImage.name}
                            className="max-w-full max-h-full object-contain"
                            onLoad={() => setImageLoading(false)}
                            onError={(e) => {
                                setImageLoading(false);
                                // Fallback if image fails to load
                                (e.target as HTMLImageElement).src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23ddd" width="400" height="300"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle"%3EPreview not available%3C/text%3E%3C/svg%3E';
                            }}
                        />
                    </div>

                    {/* Image Info */}
                    <div className="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span className="text-gray-500 block">File Name</span>
                                <span className="font-medium text-gray-900 truncate block" title={currentImage.name}>
                                    {currentImage.name}
                                </span>
                            </div>
                            <div>
                                <span className="text-gray-500 block">Size</span>
                                <span className="font-medium text-gray-900">
                                    {formatFileSize(currentImage.size)}
                                </span>
                            </div>
                            <div>
                                <span className="text-gray-500 block">Format</span>
                                <span className="font-medium text-gray-900 uppercase">
                                    {currentImage.name.split('.').pop()}
                                </span>
                            </div>
                            {currentImage.width && currentImage.height && (
                                <div>
                                    <span className="text-gray-500 block">Dimensions</span>
                                    <span className="font-medium text-gray-900">
                                        {currentImage.width} × {currentImage.height}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Navigation */}
                    <div className="px-6 py-4 bg-white border-t border-gray-200 flex items-center justify-between">
                        <SecondaryButton
                            onClick={() => handleSkip('previous')}
                            disabled={loading || session.current_image_index === 0}
                        >
                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                            Previous
                        </SecondaryButton>

                        <span className="text-sm text-gray-600">
                            Image {session.current_image_index + 1} of {session.total_images}
                        </span>

                        <SecondaryButton
                            onClick={() => handleSkip('next')}
                            disabled={loading || session.current_image_index >= session.total_images - 1}
                        >
                            Next / Skip
                            <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </SecondaryButton>
                    </div>
                </div>
            </div>

            {/* Sidebar */}
            <div className="lg:col-span-3 space-y-6">
                {/* Statistics */}
                <div className="bg-white rounded-xl shadow-lg p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Progress</h3>
                    <div className="space-y-4">
                        <div>
                            <div className="flex justify-between text-sm mb-1">
                                <span className="text-gray-600">Total</span>
                                <span className="font-medium text-gray-900">{session.total_images}</span>
                            </div>
                        </div>
                        <div>
                            <div className="flex justify-between text-sm mb-1">
                                <span className="text-gray-600">Sorted</span>
                                <span className="font-medium text-green-600">{session.sorted_images}</span>
                            </div>
                        </div>
                        <div>
                            <div className="flex justify-between text-sm mb-1">
                                <span className="text-gray-600">Remaining</span>
                                <span className="font-medium text-blue-600">{session.remaining_images}</span>
                            </div>
                        </div>
                        <div className="pt-2 border-t border-gray-200">
                            <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    className="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-300"
                                    style={{
                                        width: `${(session.sorted_images / session.total_images) * 100}%`,
                                    }}
                                ></div>
                            </div>
                            <p className="text-xs text-gray-500 mt-2 text-center">
                                {Math.round((session.sorted_images / session.total_images) * 100)}% complete
                            </p>
                        </div>
                    </div>
                </div>

                {/* Destination Folders */}
                <div className="bg-white rounded-xl shadow-lg p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Destination Folders</h3>
                    <div className="space-y-2">
                        {session.destination_folders.map((folder, index) => (
                            <button
                                key={folder.id}
                                onClick={() => handleSortImage(folder.id)}
                                disabled={loading}
                                className="w-full flex items-center p-3 bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed text-left group"
                            >
                                <div className="w-8 h-8 bg-blue-100 group-hover:bg-blue-200 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm mr-3 flex-shrink-0">
                                    {index + 1}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <span className="font-medium text-gray-900 truncate block">
                                        {folder.name}
                                    </span>
                                    <span className="text-xs text-gray-500">
                                        Press {index + 1}
                                    </span>
                                </div>
                            </button>
                        ))}
                    </div>
                </div>

                {/* Keyboard Shortcuts */}
                <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <h4 className="font-medium text-blue-900 text-sm mb-2">Keyboard Shortcuts</h4>
                    <ul className="text-xs text-blue-800 space-y-1">
                        <li><kbd className="px-1.5 py-0.5 bg-white border border-blue-300 rounded font-mono">1-9</kbd> Move to folder</li>
                        <li><kbd className="px-1.5 py-0.5 bg-white border border-blue-300 rounded font-mono">←</kbd> Previous</li>
                        <li><kbd className="px-1.5 py-0.5 bg-white border border-blue-300 rounded font-mono">→</kbd> Next/Skip</li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
