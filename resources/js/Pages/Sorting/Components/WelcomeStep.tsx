import { useState } from 'react';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';

interface Folder {
    id: string;
    name: string;
    parents?: string[];
}

interface Props {
    googleDriveConnected: boolean;
    googleDriveEmail?: string;
    onSessionCreated: (session: any) => void;
}

export default function WelcomeStep({ googleDriveConnected, googleDriveEmail, onSessionCreated }: Props) {
    const [loading, setLoading] = useState(false);
    const [folders, setFolders] = useState<Folder[]>([]);
    const [selectedFolder, setSelectedFolder] = useState<Folder | null>(null);
    const [showFolderPicker, setShowFolderPicker] = useState(false);

    const handleConnectGoogleDrive = () => {
        window.location.href = '/google-drive/connect';
    };

    const handleChooseFolder = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/google/folders');
            setFolders(response.data.folders);
            setShowFolderPicker(true);
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to load folders');
        } finally {
            setLoading(false);
        }
    };

    const handleSelectFolder = async (folder: Folder) => {
        setSelectedFolder(folder);
        setLoading(true);
        try {
            const response = await axios.post('/sorting/session', {
                source_folder_id: folder.id,
                source_folder_name: folder.name,
            });

            onSessionCreated(response.data.session);
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to create session');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div className="bg-gradient-to-r from-blue-500 to-purple-600 px-8 py-12 text-center">
                <div className="mb-6">
                    <div className="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg">
                        <svg
                            className="w-10 h-10 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                </div>
                <h1 className="text-4xl font-bold text-white mb-3">
                    EzSorting
                </h1>
                <p className="text-xl text-blue-100 mb-1">
                    Photo Organizer for Google Drive
                </p>
                <p className="text-sm text-blue-200">
                    Sort and organize your photos quickly and easily
                </p>
            </div>

            <div className="px-8 py-10">
                {!showFolderPicker ? (
                    <div className="max-w-2xl mx-auto">
                        <div className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-800 mb-4">
                                Welcome! Let's get started
                            </h2>
                            <p className="text-gray-600 mb-6">
                                EzSorting helps you organize your photos with a simple 3-step workflow:
                            </p>

                            <div className="space-y-4 mb-8">
                                <div className="flex items-start">
                                    <div className="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                                        1
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-900">Choose Source Folder</h3>
                                        <p className="text-sm text-gray-600">
                                            Select a folder from Google Drive containing your photos
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start">
                                    <div className="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                                        2
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-900">Create Destination Folders</h3>
                                        <p className="text-sm text-gray-600">
                                            Set up folders to organize your photos (max 9 for keyboard shortcuts)
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start">
                                    <div className="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-sm mr-3">
                                        3
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-900">Start Sorting</h3>
                                        <p className="text-sm text-gray-600">
                                            Preview and sort photos using mouse or keyboard shortcuts (1-9, arrows)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {!googleDriveConnected ? (
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                                <div className="flex items-start">
                                    <svg
                                        className="w-6 h-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                    <div className="flex-1">
                                        <h3 className="text-base font-semibold text-blue-900 mb-2">
                                            Connect Google Drive for Sorting
                                        </h3>
                                        <p className="text-sm text-blue-700 mb-4">
                                            To start sorting photos, you need to connect your Google Drive account. This allows the app to access and organize your photos directly in Google Drive.
                                        </p>
                                        <p className="text-xs text-blue-600 mb-4 bg-blue-100 p-2 rounded">
                                            <strong>Note:</strong> This is separate from your login. You can use a different Google account for Drive access.
                                        </p>
                                        <PrimaryButton onClick={handleConnectGoogleDrive} className="inline-flex items-center">
                                            <svg className="w-5 h-5 mr-2" viewBox="0 0 24 24">
                                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                            </svg>
                                            Connect Google Drive
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div>
                                <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                    <div className="flex items-center">
                                        <svg
                                            className="w-5 h-5 text-green-600 mr-2"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fillRule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-green-800">
                                                Google Drive Connected
                                            </p>
                                            {googleDriveEmail && (
                                                <p className="text-xs text-green-700 mt-0.5">
                                                    {googleDriveEmail}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="text-center">
                                    <PrimaryButton
                                        onClick={handleChooseFolder}
                                        disabled={loading}
                                        className="px-8 py-3 text-lg"
                                    >
                                        {loading ? 'Loading...' : 'Choose Source Folder'}
                                    </PrimaryButton>
                                </div>
                            </div>
                        )}
                    </div>
                ) : (
                    <div>
                        <div className="mb-6 flex items-center justify-between">
                            <h2 className="text-2xl font-semibold text-gray-800">
                                Select Source Folder
                            </h2>
                            <button
                                onClick={() => setShowFolderPicker(false)}
                                className="text-gray-500 hover:text-gray-700"
                            >
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            {folders.map((folder) => (
                                <button
                                    key={folder.id}
                                    onClick={() => handleSelectFolder(folder)}
                                    disabled={loading}
                                    className="flex items-center p-4 bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-150 text-left disabled:opacity-50"
                                >
                                    <svg
                                        className="w-8 h-8 text-blue-500 mr-3 flex-shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                    </svg>
                                    <span className="font-medium text-gray-900 truncate">
                                        {folder.name}
                                    </span>
                                </button>
                            ))}
                        </div>

                        {folders.length === 0 && (
                            <div className="text-center py-12 text-gray-500">
                                No folders found
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
